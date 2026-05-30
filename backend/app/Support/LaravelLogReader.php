<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SplFileInfo;

class LaravelLogReader
{
    public function __construct(
        private readonly ?string $directory = null,
        private readonly int $maxBytes = 2097152,
    ) {}

    /**
     * @return array<int, array{name: string, size: int, formatted_size: string, updated_at: string|null}>
     */
    public function files(): array
    {
        if (! File::isDirectory($this->directory())) {
            return [];
        }

        return collect(File::files($this->directory()))
            ->filter(fn (SplFileInfo $file): bool => $this->isLogFile($file))
            ->sortByDesc(fn (SplFileInfo $file): int => $file->getMTime())
            ->map(fn (SplFileInfo $file): array => [
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'formatted_size' => $this->formatBytes($file->getSize()),
                'updated_at' => Carbon::createFromTimestamp($file->getMTime())->toDateTimeString(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{name: string|null, size: int, formatted_size: string, updated_at: string|null, path: string|null}
     */
    public function meta(?string $filename = null): array
    {
        $path = $this->pathFor($filename);

        if ($path === null) {
            return [
                'name' => null,
                'size' => 0,
                'formatted_size' => '0 B',
                'updated_at' => null,
                'path' => null,
            ];
        }

        $size = File::size($path);

        return [
            'name' => basename($path),
            'size' => $size,
            'formatted_size' => $this->formatBytes($size),
            'updated_at' => Carbon::createFromTimestamp(File::lastModified($path))->toDateTimeString(),
            'path' => $path,
        ];
    }

    /**
     * @param  array{level?: string|null, date?: string|null, search?: string|null}  $filters
     * @return array<int, array{timestamp: string, environment: string, level: string, message: string, context: string, context_line_count: int, truncated: bool}>
     */
    public function entries(?string $filename = null, array $filters = [], int $limit = 100): array
    {
        $path = $this->pathFor($filename);

        if ($path === null) {
            return [];
        }

        $entries = $this->parse($this->readTail($path));

        return collect($entries)
            ->reverse()
            ->filter(fn (array $entry): bool => $this->matchesFilters($entry, $filters))
            ->take($limit)
            ->values()
            ->all();
    }

    private function directory(): string
    {
        return $this->directory ?? storage_path('logs');
    }

    private function isLogFile(SplFileInfo $file): bool
    {
        $filename = $file->getFilename();

        return $file->isFile()
            && ($filename === 'laravel.log' || Str::endsWith($filename, '.log'));
    }

    private function pathFor(?string $filename = null): ?string
    {
        $files = collect($this->files())->pluck('name')->all();
        $filename = basename((string) ($filename ?: ($files[0] ?? '')));

        if ($filename === '' || ! in_array($filename, $files, true)) {
            return null;
        }

        $path = $this->directory().DIRECTORY_SEPARATOR.$filename;

        return File::isFile($path) ? $path : null;
    }

    private function readTail(string $path): string
    {
        $size = File::size($path);
        $length = min($size, $this->maxBytes);

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return '';
        }

        if ($length < $size) {
            fseek($handle, -$length, SEEK_END);
        }

        $contents = stream_get_contents($handle);
        fclose($handle);

        return $contents === false ? '' : $contents;
    }

    /**
     * @return array<int, array{timestamp: string, environment: string, level: string, message: string, context: string, context_line_count: int, truncated: bool}>
     */
    private function parse(string $contents): array
    {
        $entries = [];
        $current = null;

        foreach (preg_split('/\R/', $contents) ?: [] as $line) {
            if (preg_match('/^\[(?<timestamp>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+(?<environment>[\w-]+)\.(?<level>[A-Z]+):\s?(?<message>.*)$/', $line, $matches)) {
                if ($current !== null) {
                    $entries[] = $this->normalizeEntry($current);
                }

                $current = [
                    'timestamp' => $matches['timestamp'],
                    'environment' => $matches['environment'],
                    'level' => $matches['level'],
                    'message' => trim($matches['message']),
                    'context' => [],
                ];

                continue;
            }

            if ($current !== null) {
                $current['context'][] = $line;
            }
        }

        if ($current !== null) {
            $entries[] = $this->normalizeEntry($current);
        }

        return $entries;
    }

    /**
     * @param  array{timestamp: string, environment: string, level: string, message: string, context: array<int, string>}  $entry
     * @return array{timestamp: string, environment: string, level: string, message: string, context: string, context_line_count: int, truncated: bool}
     */
    private function normalizeEntry(array $entry): array
    {
        $contextLines = array_values(array_filter(
            $entry['context'],
            fn (string $line): bool => trim($line) !== '',
        ));
        $truncated = count($contextLines) > 80;

        return [
            'timestamp' => $entry['timestamp'],
            'environment' => $entry['environment'],
            'level' => $entry['level'],
            'message' => $entry['message'],
            'context' => implode(PHP_EOL, array_slice($contextLines, 0, 80)),
            'context_line_count' => count($contextLines),
            'truncated' => $truncated,
        ];
    }

    /**
     * @param  array{timestamp: string, environment: string, level: string, message: string, context: string}  $entry
     * @param  array{level?: string|null, date?: string|null, search?: string|null}  $filters
     */
    private function matchesFilters(array $entry, array $filters): bool
    {
        $level = strtoupper(trim((string) ($filters['level'] ?? '')));

        if ($level !== '' && $entry['level'] !== $level) {
            return false;
        }

        $date = trim((string) ($filters['date'] ?? ''));

        if ($date !== '' && ! str_starts_with($entry['timestamp'], $date)) {
            return false;
        }

        $search = Str::lower(trim((string) ($filters['search'] ?? '')));

        if ($search !== '') {
            $haystack = Str::lower($entry['message'].' '.$entry['context']);

            if (! str_contains($haystack, $search)) {
                return false;
            }
        }

        return true;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return "{$bytes} B";
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / 1048576, 1).' MB';
    }
}
