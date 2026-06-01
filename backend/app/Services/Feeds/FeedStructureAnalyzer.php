<?php

namespace App\Services\Feeds;

use App\Models\Feed;
use App\Models\FeedMappingProfile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class FeedStructureAnalyzer
{
    /**
     * @return array{
     *     available_elements: array<int, array{path: string, label: string, count: int}>,
     *     sample_fields: array<int, array{path: string, label: string, sample: mixed}>,
     *     sample_payload: array<string, mixed>,
     *     row_selector: string|null
     * }
     */
    public function analyze(Feed $feed): array
    {
        $feed->loadMissing('mappingProfile');

        $profile = $feed->mappingProfile;

        if (! $profile) {
            throw new RuntimeException('A mapping profile is required before a feed can be analyzed.');
        }

        $payload = $this->fetchPayload($feed);
        $profile->forceFill([
            'source_format' => $feed->source_format ?: $profile->source_format,
            'source_encoding' => $feed->source_encoding ?: $profile->source_encoding,
            'delimiter' => $feed->delimiter,
            'enclosure' => $feed->enclosure,
            'decimal_separator' => $feed->decimal_separator ?: '.',
            'thousands_separator' => $feed->thousands_separator,
            'row_selector' => $feed->row_selector ?: $profile->row_selector,
            'first_row_is_header' => $feed->first_row_is_header ?? true,
        ]);

        return match ($feed->source_format ?: $profile->source_format) {
            'json', 'jsonl' => $this->analyzeJson($payload, $profile),
            'xml' => $this->analyzeXml($payload, $profile),
            default => $this->analyzeCsv($payload, $profile),
        };
    }

    private function fetchPayload(Feed $feed): string
    {
        if ($feed->source_type === 'file') {
            $source = trim((string) ($feed->source_file_path ?: $feed->source_url));

            if ($source === '') {
                throw new RuntimeException('Upload a feed file before analyzing this feed.');
            }

            if (Storage::disk('local')->exists($source)) {
                return Storage::disk('local')->get($source);
            }

            if (File::isFile($source)) {
                return (string) File::get($source);
            }

            throw new RuntimeException('The uploaded feed file could not be found.');
        }

        $source = trim((string) $feed->source_url);

        if ($source === '') {
            throw new RuntimeException('The feed source URL is empty.');
        }

        $response = Http::timeout(20)
            ->withHeaders($feed->request_headers ?? [])
            ->get($source, $feed->request_query_params ?? []);

        if (! $response->successful()) {
            throw new RuntimeException("The feed returned HTTP {$response->status()}.");
        }

        return $response->body();
    }

    /**
     * @return array<string, mixed>
     */
    private function analyzeJson(string $payload, FeedMappingProfile $profile): array
    {
        if ($profile->source_format === 'jsonl') {
            $rows = collect(preg_split('/\R/', trim($payload)) ?: [])
                ->filter()
                ->take(20)
                ->map(fn (string $line): mixed => json_decode($line, true, flags: JSON_THROW_ON_ERROR))
                ->values()
                ->all();

            return $this->buildAnalysisFromRows('rows', $rows, [['path' => 'rows', 'label' => 'rows', 'count' => count($rows)]], $profile);
        }

        $decoded = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
        $candidates = $this->findRowCandidates($decoded);
        $selectedPath = $this->selectRowPath($profile, $candidates);
        $rows = $this->rowsAtPath($decoded, $selectedPath);

        return $this->buildAnalysisFromRows($selectedPath, $rows, $candidates, $profile);
    }

    /**
     * @return array<string, mixed>
     */
    private function analyzeXml(string $payload, FeedMappingProfile $profile): array
    {
        $previous = libxml_use_internal_errors(true);

        try {
            $xml = simplexml_load_string($payload, 'SimpleXMLElement', LIBXML_NOCDATA);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if (! $xml) {
            throw new RuntimeException('The XML feed could not be parsed.');
        }

        $decoded = json_decode(json_encode($xml, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
        $candidates = $this->findRowCandidates($decoded);
        $selectedPath = $this->selectRowPath($profile, $candidates);
        $rows = $this->rowsAtPath($decoded, $selectedPath);

        return $this->buildAnalysisFromRows($selectedPath, $rows, $candidates, $profile);
    }

    /**
     * @return array<string, mixed>
     */
    private function analyzeCsv(string $payload, FeedMappingProfile $profile): array
    {
        $lines = collect(preg_split('/\R/', trim($payload)) ?: [])
            ->filter(fn (string $line): bool => trim($line) !== '')
            ->take(21)
            ->values();

        if ($lines->isEmpty()) {
            throw new RuntimeException('The CSV feed is empty.');
        }

        $delimiter = $profile->delimiter ?: ',';
        $enclosure = $profile->enclosure ?: '"';
        $parsed = $lines
            ->map(fn (string $line): array => str_getcsv($line, $delimiter, $enclosure))
            ->values()
            ->all();

        $headers = $profile->first_row_is_header
            ? array_map(fn (mixed $header): string => trim((string) $header), array_shift($parsed))
            : array_map(fn (int $index): string => "column_{$index}", array_keys($parsed[0] ?? []));

        $rows = array_map(function (array $row) use ($headers): array {
            $mapped = [];

            foreach ($headers as $index => $header) {
                $mapped[$header ?: "column_{$index}"] = $row[$index] ?? null;
            }

            return $mapped;
        }, $parsed);

        return $this->buildAnalysisFromRows('rows', $rows, [
            ['path' => 'rows', 'label' => 'rows', 'count' => count($rows)],
        ], $profile);
    }

    /**
     * @param  mixed  $node
     * @return array<int, array{path: string, label: string, count: int}>
     */
    private function findRowCandidates(mixed $node, string $path = '$'): array
    {
        if (! is_array($node)) {
            return [];
        }

        $candidates = [];

        if ($this->isListOfRows($node)) {
            $candidates[] = [
                'path' => $path,
                'label' => "{$path} (".count($node).' items)',
                'count' => count($node),
            ];
        }

        foreach ($node as $key => $value) {
            if (! is_array($value)) {
                continue;
            }

            $childPath = $path === '$' ? (string) $key : "{$path}.{$key}";
            $candidates = [
                ...$candidates,
                ...$this->findRowCandidates($value, $childPath),
            ];
        }

        return collect($candidates)
            ->unique('path')
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>  $node
     */
    private function isListOfRows(array $node): bool
    {
        if (! array_is_list($node) || $node === []) {
            return false;
        }

        $first = $node[0] ?? null;

        return is_array($first);
    }

    /**
     * @param  array<int, array{path: string, label: string, count: int}>  $candidates
     */
    private function selectRowPath(FeedMappingProfile $profile, array $candidates): string
    {
        $configured = trim((string) $profile->row_selector);

        if ($configured !== '') {
            return $configured;
        }

        return $candidates[0]['path'] ?? '$';
    }

    /**
     * @return array<int, mixed>
     */
    private function rowsAtPath(mixed $decoded, string $path): array
    {
        $rows = $path === '$' ? $decoded : Arr::get($decoded, $path);

        if (! is_array($rows)) {
            return [];
        }

        if ($this->isListOfRows($rows)) {
            return array_slice($rows, 0, 20);
        }

        return [$rows];
    }

    /**
     * @param  array<int, mixed>  $rows
     * @param  array<int, array{path: string, label: string, count: int}>  $candidates
     * @return array<string, mixed>
     */
    private function buildAnalysisFromRows(string $selectedPath, array $rows, array $candidates, FeedMappingProfile $profile): array
    {
        $samplePayload = $rows[0] ?? [];
        $fields = [];

        foreach (array_slice($rows, 0, 5) as $row) {
            if (! is_array($row)) {
                continue;
            }

            foreach (Arr::dot($row) as $path => $value) {
                $fields[$path] ??= [
                    'path' => (string) $path,
                    'label' => (string) $path,
                    'sample' => $this->formatSample($value),
                ];
            }
        }

        return [
            'available_elements' => $candidates ?: [
                ['path' => $selectedPath, 'label' => $selectedPath, 'count' => count($rows)],
            ],
            'sample_fields' => array_values($fields),
            'sample_payload' => is_array($samplePayload) ? $samplePayload : ['value' => $samplePayload],
            'row_selector' => $profile->row_selector ?: $selectedPath,
        ];
    }

    private function formatSample(mixed $value): mixed
    {
        if (is_scalar($value) || $value === null) {
            return Str::limit((string) $value, 160);
        }

        try {
            return Str::limit(json_encode($value, JSON_THROW_ON_ERROR), 160);
        } catch (Throwable) {
            return null;
        }
    }
}
