<?php

namespace App\Services\Feeds;

use App\Models\CanonicalField;
use App\Models\Feed;
use App\Models\FeedProductFieldMapping;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class FeedRowMapper
{
    /**
     * Map a source feed row to canonical product field keys for one Feed.
     *
     * @param  array<string, mixed>  $sourceRow
     * @return array<string, mixed>
     */
    public function mapFeed(array $sourceRow, Feed $feed): array
    {
        $feed->loadMissing(['productFieldMappings.canonicalField']);

        $canonical = [];

        foreach ($feed->productFieldMappings->sortBy('sort_order') as $mapping) {
            if ($mapping->mapping_action === 'skip') {
                continue;
            }

            $field = $mapping->canonicalField;

            if (! $field || ! $field->is_active) {
                continue;
            }

            $value = $this->valueForMapping($sourceRow, $mapping);
            $value = $this->transform($value, $mapping, $feed);

            if ($this->hasValue($value)) {
                $canonical[$field->key] = $value;
            }
        }

        return $canonical;
    }

    /**
     * Map a source feed row directly to product table attributes for one Feed.
     *
     * @param  array<string, mixed>  $sourceRow
     * @return array<string, mixed>
     */
    public function mapFeedToProductAttributes(array $sourceRow, Feed $feed): array
    {
        $feed->loadMissing(['productFieldMappings.canonicalField']);

        $attributes = [
            'raw_payload' => $sourceRow,
        ];
        $metadata = [];

        foreach ($feed->productFieldMappings->sortBy('sort_order') as $mapping) {
            if ($mapping->mapping_action === 'skip') {
                continue;
            }

            $field = $mapping->canonicalField;

            if (! $field || ! $field->is_active) {
                continue;
            }

            $value = $this->transform($this->valueForMapping($sourceRow, $mapping), $mapping, $feed);

            if (! $this->hasValue($value)) {
                continue;
            }

            if ($field->target_column) {
                $attributes[$field->target_column] = $value;

                continue;
            }

            if ($field->metadata_path) {
                Arr::set($metadata, $field->metadata_path, $value);
            }
        }

        if ($metadata !== []) {
            $attributes['metadata'] = $metadata;
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $canonicalRow
     * @return array<int, string>
     */
    public function missingRequiredFeedFields(array $canonicalRow, Feed $feed): array
    {
        $feed->loadMissing(['productFieldMappings.canonicalField']);

        static $requiredKeys = null;

        $requiredKeys ??= CanonicalField::query()
            ->active()
            ->where('is_required', true)
            ->pluck('key')
            ->all();

        $mappingRequiredKeys = $feed->productFieldMappings
            ->filter(fn (FeedProductFieldMapping $mapping): bool => $mapping->mapping_action !== 'skip'
                && ($mapping->is_required || (bool) $mapping->canonicalField?->is_required))
            ->map(fn (FeedProductFieldMapping $mapping): ?string => $mapping->canonicalField?->key)
            ->filter()
            ->values()
            ->all();

        return collect([...$requiredKeys, ...$mappingRequiredKeys])
            ->unique()
            ->filter(fn (string $key): bool => ! $this->hasValue($canonicalRow[$key] ?? null))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $sourceRow
     */
    private function valueForMapping(array $sourceRow, FeedProductFieldMapping $mapping): mixed
    {
        $keys = array_values(array_filter([
            $mapping->source_field,
            $mapping->source_path,
            ...($mapping->fallback_fields ?? []),
        ]));

        foreach ($keys as $key) {
            $value = $this->readSourceValue($sourceRow, $key);

            if ($this->hasValue($value)) {
                return $value;
            }
        }

        return $mapping->default_value;
    }

    /**
     * @param  array<string, mixed>  $sourceRow
     */
    private function readSourceValue(array $sourceRow, string $key): mixed
    {
        if (array_key_exists($key, $sourceRow)) {
            return $sourceRow[$key];
        }

        if (str_contains($key, '*')) {
            return $this->readWildcardValues($sourceRow, $key);
        }

        $pathValue = $this->readArrayPath($sourceRow, $key);

        if ($this->hasValue($pathValue)) {
            return $pathValue;
        }

        $selectedValue = $this->readSelectorValue($sourceRow, $key);

        if ($this->hasValue($selectedValue)) {
            return $selectedValue;
        }

        $dotRow = Arr::dot($sourceRow);

        if (array_key_exists($key, $dotRow)) {
            return $dotRow[$key];
        }

        $lowerKey = Str::lower($key);

        foreach ($dotRow as $sourceKey => $value) {
            if (Str::lower((string) $sourceKey) === $lowerKey) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $sourceRow
     * @return array<int, mixed>
     */
    private function readWildcardValues(array $sourceRow, string $pattern): array
    {
        $dotRow = Arr::dot($sourceRow);
        $normalizedPattern = $this->normalizeFieldPattern($pattern);
        $values = [];

        foreach ($dotRow as $sourceKey => $value) {
            if (! $this->hasValue($value)) {
                continue;
            }

            $normalizedSourceKey = $this->normalizeFieldPattern((string) $sourceKey);

            if (! Str::is($normalizedPattern, $normalizedSourceKey)) {
                continue;
            }

            $values[] = $value;
        }

        return array_values(array_unique($values, SORT_REGULAR));
    }

    private function normalizeFieldPattern(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->replaceMatches('/[^a-z0-9*]+/', '_')
            ->trim('_')
            ->toString();
    }

    /**
     * Read selector paths such as properties.property[name=brand].value.
     *
     * @param  array<string, mixed>  $sourceRow
     */
    private function readSelectorValue(array $sourceRow, string $key): mixed
    {
        if (! str_contains($key, '[')) {
            return null;
        }

        $node = $sourceRow;

        foreach (explode('.', $key) as $segment) {
            if (preg_match('/^([^\[]+)\[([^=\]]+)=([^\]]+)\]$/', $segment, $matches) === 1) {
                $node = $this->readArrayKey($node, $matches[1]);

                if (! is_array($node)) {
                    return null;
                }

                $filterKey = trim($matches[2]);
                $filterValue = trim($matches[3], " \t\n\r\0\x0B\"'");
                $items = array_is_list($node) ? $node : [$node];
                $match = null;

                foreach ($items as $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $itemValue = $this->readArrayPath($item, $filterKey)
                        ?? $this->readArrayPath($item, '@attributes.'.$filterKey);

                    if (Str::lower(trim((string) $itemValue)) === Str::lower($filterValue)) {
                        $match = $item;

                        break;
                    }
                }

                if ($match === null) {
                    return null;
                }

                $node = $match;

                continue;
            }

            $node = $this->readArrayKey($node, $segment);

            if ($node === null) {
                return null;
            }
        }

        return $node;
    }

    private function readArrayPath(mixed $node, string $path): mixed
    {
        foreach (explode('.', $path) as $segment) {
            $node = $this->readArrayKey($node, $segment);

            if ($node === null) {
                return null;
            }
        }

        return $node;
    }

    private function readArrayKey(mixed $node, string $key): mixed
    {
        if (! is_array($node)) {
            return null;
        }

        if (array_key_exists($key, $node)) {
            return $node[$key];
        }

        $lowerKey = Str::lower($key);

        foreach ($node as $nodeKey => $value) {
            if (Str::lower((string) $nodeKey) === $lowerKey) {
                return $value;
            }
        }

        return null;
    }

    private function transform(mixed $value, FeedProductFieldMapping $mapping, Feed $context): mixed
    {
        if (! $this->hasValue($value)) {
            return $mapping->default_value;
        }

        if ($mapping->mapping_action === 'default') {
            return $mapping->default_value;
        }

        if (is_string($value)) {
            $value = trim($value);
        }

        return match ($mapping->transform_type) {
            'array' => $this->normalizeArray($value, $mapping->transform_config ?? []),
            'availability' => $this->normalizeAvailability($value),
            'boolean' => $this->normalizeBoolean($value),
            'decimal', 'money' => $this->normalizeDecimal($value, $context),
            'first_value' => $this->normalizeFirstValue($value),
            'integer' => $this->normalizeInteger($value),
            'lowercase' => Str::lower((string) $value),
            'stock_availability' => $this->normalizeStockAvailability($value),
            'uppercase' => Str::upper((string) $value),
            'url', 'trim', 'copy' => $value,
            default => $value,
        };
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<int, mixed>
     */
    private function normalizeArray(mixed $value, array $config): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value, fn (mixed $item): bool => $this->hasValue($item)));
        }

        $delimiter = $config['delimiter'] ?? '|';

        return array_values(array_filter(
            array_map('trim', explode($delimiter, (string) $value)),
            fn (string $item): bool => $item !== ''
        ));
    }

    private function normalizeFirstValue(mixed $value): ?string
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                if (! $this->hasValue($item)) {
                    continue;
                }

                if (is_array($item)) {
                    $nestedValue = $this->normalizeFirstValue($item);

                    if ($this->hasValue($nestedValue)) {
                        return $nestedValue;
                    }

                    continue;
                }

                return trim((string) $item);
            }

            return null;
        }

        if (! $this->hasValue($value)) {
            return null;
        }

        return trim((string) $value);
    }

    private function normalizeStockAvailability(mixed $value): string
    {
        if (is_numeric($value)) {
            return (float) $value > 0 ? 'in_stock' : 'out_of_stock';
        }

        return $this->normalizeAvailability($value);
    }

    private function normalizeAvailability(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'in_stock' : 'out_of_stock';
        }

        $normalized = Str::of((string) $value)->lower()->trim()->replace([' ', '-'], '_')->toString();

        return match ($normalized) {
            '1', 'true', 'yes', 'y', 'available', 'in_stock', 'instock', 'op_voorraad' => 'in_stock',
            '0', 'false', 'no', 'n', 'unavailable', 'out_of_stock', 'outofstock', 'niet_op_voorraad' => 'out_of_stock',
            'preorder', 'pre_order', 'pre_ordered' => 'preorder',
            'backorder', 'back_order' => 'backorder',
            default => $normalized,
        };
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = Str::of((string) $value)->lower()->trim()->toString();

        return in_array($normalized, ['1', 'true', 'yes', 'y', 'ja'], true);
    }

    private function normalizeDecimal(mixed $value, Feed $context): ?string
    {
        if (is_numeric($value)) {
            return number_format((float) $value, 2, '.', '');
        }

        $normalized = trim((string) $value);

        if ($context->thousands_separator) {
            $normalized = str_replace($context->thousands_separator, '', $normalized);
        }

        if ($context->decimal_separator && $context->decimal_separator !== '.') {
            $normalized = str_replace($context->decimal_separator, '.', $normalized);
        }

        if (! str_contains($normalized, '.') && substr_count($normalized, ',') === 1) {
            $normalized = str_replace(',', '.', $normalized);
        }

        $normalized = preg_replace('/[^0-9.\-]/', '', $normalized);

        if ($normalized === '' || $normalized === null || ! is_numeric($normalized)) {
            return null;
        }

        return number_format((float) $normalized, 2, '.', '');
    }

    private function normalizeInteger(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        $normalized = preg_replace('/[^0-9\-]/', '', (string) $value);

        if ($normalized === '' || $normalized === null || ! is_numeric($normalized)) {
            return null;
        }

        return (int) $normalized;
    }

    private function hasValue(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value) && trim($value) === '') {
            return false;
        }

        if (is_array($value) && $value === []) {
            return false;
        }

        return true;
    }
}
