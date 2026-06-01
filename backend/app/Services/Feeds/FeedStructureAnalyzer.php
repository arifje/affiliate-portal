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
        $profile = $this->profileFromFeed($feed);
        $payload = $this->fetchPayload($feed);

        return match ($profile->source_format) {
            'json', 'jsonl' => $this->analyzeJson($payload, $profile),
            'xml' => $this->analyzeXml($payload, $profile),
            default => $this->analyzeCsv($payload, $profile),
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function extractRows(Feed $feed, ?int $limit = null): array
    {
        $profile = $this->profileFromFeed($feed);
        $payload = $this->fetchPayload($feed);

        return match ($profile->source_format) {
            'json', 'jsonl' => $this->extractJsonRows($payload, $profile, $limit),
            'xml' => $this->extractXmlRows($payload, $profile, $limit),
            default => $this->extractCsvRows($payload, $profile, $limit),
        };
    }

    public function fetchPayload(Feed $feed): string
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

    private function profileFromFeed(Feed $feed): FeedMappingProfile
    {
        $profile = $feed->mappingProfile ?: new FeedMappingProfile();

        $profile->forceFill([
            'source_format' => $feed->source_format ?: $profile->source_format ?: 'csv',
            'source_encoding' => $feed->source_encoding ?: $profile->source_encoding ?: 'utf-8',
            'delimiter' => $feed->delimiter ?: $profile->delimiter ?: ',',
            'enclosure' => $feed->enclosure ?: $profile->enclosure ?: '"',
            'decimal_separator' => $feed->decimal_separator ?: $profile->decimal_separator ?: '.',
            'thousands_separator' => $feed->thousands_separator ?: $profile->thousands_separator,
            'row_selector' => $feed->row_selector ?: $profile->row_selector,
            'first_row_is_header' => $feed->first_row_is_header ?? $profile->first_row_is_header ?? true,
        ]);

        return $profile;
    }

    /**
     * @return array<string, mixed>
     */
    private function analyzeJson(string $payload, FeedMappingProfile $profile): array
    {
        [$rows, $candidates, $selectedPath] = $this->jsonRowsAndCandidates($payload, $profile, 20);

        return $this->buildAnalysisFromRows($selectedPath, $rows, $candidates, $profile);
    }

    /**
     * @return array<string, mixed>
     */
    private function analyzeXml(string $payload, FeedMappingProfile $profile): array
    {
        [$rows, $candidates, $selectedPath] = $this->xmlRowsAndCandidates($payload, $profile, 20);

        return $this->buildAnalysisFromRows($selectedPath, $rows, $candidates, $profile);
    }

    /**
     * @return array<string, mixed>
     */
    private function analyzeCsv(string $payload, FeedMappingProfile $profile): array
    {
        $rows = $this->extractCsvRows($payload, $profile, 20);

        return $this->buildAnalysisFromRows('rows', $rows, [
            ['path' => 'rows', 'label' => 'rows', 'count' => count($rows)],
        ], $profile);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractJsonRows(string $payload, FeedMappingProfile $profile, ?int $limit = null): array
    {
        return $this->jsonRowsAndCandidates($payload, $profile, $limit)[0];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractXmlRows(string $payload, FeedMappingProfile $profile, ?int $limit = null): array
    {
        return $this->xmlRowsAndCandidates($payload, $profile, $limit)[0];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractCsvRows(string $payload, FeedMappingProfile $profile, ?int $limit = null): array
    {
        $delimiter = $profile->delimiter ?: ',';
        $enclosure = $profile->enclosure ?: '"';
        $handle = fopen('php://temp', 'r+');

        if (! $handle) {
            throw new RuntimeException('The CSV feed could not be read.');
        }

        fwrite($handle, $payload);
        rewind($handle);

        $headers = null;
        $rows = [];
        $rowIndex = 0;

        while (($row = fgetcsv($handle, null, $delimiter, $enclosure, '')) !== false) {
            if ($row === [null] || $row === []) {
                continue;
            }

            if ($headers === null) {
                $headers = $profile->first_row_is_header
                    ? array_map(fn (mixed $header): string => trim((string) $header), $row)
                    : array_map(fn (int $index): string => "column_{$index}", array_keys($row));

                if ($profile->first_row_is_header) {
                    continue;
                }
            }

            $mapped = [];

            foreach ($headers as $index => $header) {
                $mapped[$header ?: "column_{$index}"] = $row[$index] ?? null;
            }

            $rows[] = $mapped;
            $rowIndex++;

            if ($limit !== null && $rowIndex >= $limit) {
                break;
            }
        }

        fclose($handle);

        if ($rows === []) {
            throw new RuntimeException('The CSV feed is empty.');
        }

        return $rows;
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array{path: string, label: string, count: int}>, 2: string}
     */
    private function jsonRowsAndCandidates(string $payload, FeedMappingProfile $profile, ?int $limit): array
    {
        if ($profile->source_format === 'jsonl') {
            $rows = collect(preg_split('/\R/', trim($payload)) ?: [])
                ->filter()
                ->when($limit !== null, fn ($collection) => $collection->take($limit))
                ->map(fn (string $line): mixed => json_decode($line, true, flags: JSON_THROW_ON_ERROR))
                ->filter(fn (mixed $row): bool => is_array($row))
                ->values()
                ->all();

            return [$rows, [['path' => 'rows', 'label' => 'rows', 'count' => count($rows)]], 'rows'];
        }

        $decoded = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
        $candidates = $this->findRowCandidates($decoded);
        $selectedPath = $this->selectRowPath($profile, $candidates);
        $rows = $this->rowsAtPath($decoded, $selectedPath, $limit);

        return [$rows, $candidates, $selectedPath];
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array{path: string, label: string, count: int}>, 2: string}
     */
    private function xmlRowsAndCandidates(string $payload, FeedMappingProfile $profile, ?int $limit): array
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
        $rows = $this->rowsAtPath($decoded, $selectedPath, $limit);

        return [$rows, $candidates, $selectedPath];
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
    private function rowsAtPath(mixed $decoded, string $path, ?int $limit = null): array
    {
        $rows = $path === '$' ? $decoded : Arr::get($decoded, $path);

        if (! is_array($rows)) {
            return [];
        }

        if ($this->isListOfRows($rows)) {
            $rows = $limit === null ? $rows : array_slice($rows, 0, $limit);

            return array_values(array_filter($rows, fn (mixed $row): bool => is_array($row)));
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
