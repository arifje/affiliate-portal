<?php

namespace App\Services\Feeds;

use App\Models\Feed;
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
        $payload = $this->fetchPayload($feed);

        return match ($feed->source_format) {
            'json', 'jsonl' => $this->analyzeJson($payload, $feed),
            'xml' => $this->analyzeXml($payload, $feed),
            default => $this->analyzeCsv($payload, $feed),
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function extractRows(Feed $feed, ?int $limit = null): array
    {
        $payload = $this->fetchPayload($feed);

        return match ($feed->source_format) {
            'json', 'jsonl' => $this->extractJsonRows($payload, $feed, $limit),
            'xml' => $this->extractXmlRows($payload, $feed, $limit),
            default => $this->extractCsvRows($payload, $feed, $limit),
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

    /**
     * @return array<string, mixed>
     */
    private function analyzeJson(string $payload, Feed $feed): array
    {
        [$rows, $candidates, $selectedPath] = $this->jsonRowsAndCandidates($payload, $feed, 20);

        return $this->buildAnalysisFromRows($selectedPath, $rows, $candidates, $feed);
    }

    /**
     * @return array<string, mixed>
     */
    private function analyzeXml(string $payload, Feed $feed): array
    {
        [$rows, $candidates, $selectedPath] = $this->xmlRowsAndCandidates($payload, $feed, 20);

        return $this->buildAnalysisFromRows($selectedPath, $rows, $candidates, $feed);
    }

    /**
     * @return array<string, mixed>
     */
    private function analyzeCsv(string $payload, Feed $feed): array
    {
        $rows = $this->extractCsvRows($payload, $feed, 20);

        return $this->buildAnalysisFromRows('rows', $rows, [
            ['path' => 'rows', 'label' => 'rows', 'count' => count($rows)],
        ], $feed);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractJsonRows(string $payload, Feed $feed, ?int $limit = null): array
    {
        return $this->jsonRowsAndCandidates($payload, $feed, $limit)[0];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractXmlRows(string $payload, Feed $feed, ?int $limit = null): array
    {
        return $this->xmlRowsAndCandidates($payload, $feed, $limit)[0];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractCsvRows(string $payload, Feed $feed, ?int $limit = null): array
    {
        $enclosure = $feed->enclosure ?: '"';
        $delimiter = $this->detectCsvDelimiter($payload, $feed->delimiter ?: ',', $enclosure);
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
                $headers = ($feed->first_row_is_header ?? true)
                    ? array_map(fn (mixed $header): string => trim((string) $header), $row)
                    : array_map(fn (int $index): string => "column_{$index}", array_keys($row));

                if ($feed->first_row_is_header ?? true) {
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

    private function detectCsvDelimiter(string $payload, string $configuredDelimiter, string $enclosure): string
    {
        $firstLine = strtok(ltrim($payload, "\xEF\xBB\xBF"), "\r\n") ?: '';

        if ($firstLine === '') {
            return $configuredDelimiter;
        }

        $configuredColumns = str_getcsv($firstLine, $configuredDelimiter, $enclosure, '');

        if (count($configuredColumns) > 1) {
            return $configuredDelimiter;
        }

        $bestDelimiter = $configuredDelimiter;
        $bestColumnCount = count($configuredColumns);

        foreach ([',', ';', '|'] as $delimiter) {
            $columnCount = count(str_getcsv($firstLine, $delimiter, $enclosure, ''));

            if ($columnCount > $bestColumnCount) {
                $bestDelimiter = $delimiter;
                $bestColumnCount = $columnCount;
            }
        }

        return $bestDelimiter;
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array{path: string, label: string, count: int}>, 2: string}
     */
    private function jsonRowsAndCandidates(string $payload, Feed $feed, ?int $limit): array
    {
        if ($feed->source_format === 'jsonl') {
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
        $selectedPath = $this->selectRowPath($feed, $candidates);
        $rows = $this->rowsAtPath($decoded, $selectedPath, $limit);

        return [$rows, $candidates, $selectedPath];
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array{path: string, label: string, count: int}>, 2: string}
     */
    private function xmlRowsAndCandidates(string $payload, Feed $feed, ?int $limit): array
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
        $selectedPath = $this->selectRowPath($feed, $candidates);
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
    private function selectRowPath(Feed $feed, array $candidates): string
    {
        $configured = trim((string) $feed->row_selector);

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
    private function buildAnalysisFromRows(string $selectedPath, array $rows, array $candidates, Feed $feed): array
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
            'row_selector' => $feed->row_selector ?: $selectedPath,
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
