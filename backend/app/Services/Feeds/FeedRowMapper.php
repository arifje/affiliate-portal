<?php

namespace App\Services\Feeds;

use App\Models\CanonicalField;
use App\Models\Feed;
use App\Models\FeedFieldMapping;
use App\Models\FeedMappingProfile;
use App\Models\FeedProductFieldMapping;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class FeedRowMapper
{
    /**
     * Map a source feed row to canonical product keys.
     *
     * @param  array<string, mixed>  $sourceRow
     * @return array<string, mixed>
     */
    public function map(array $sourceRow, FeedMappingProfile $profile): array
    {
        $profile->loadMissing(['fieldMappings.canonicalField']);

        $canonical = [];

        foreach ($profile->fieldMappings->sortBy('sort_order') as $mapping) {
            if ($mapping->mapping_action === 'skip') {
                continue;
            }

            $field = $mapping->canonicalField;

            if (! $field || ! $field->is_active) {
                continue;
            }

            $value = $this->valueForMapping($sourceRow, $mapping);
            $value = $this->transform($value, $mapping, $profile);

            if ($this->hasValue($value)) {
                $canonical[$field->key] = $value;
            }
        }

        return $canonical;
    }

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
     * Map a source feed row directly to product table attributes.
     *
     * @param  array<string, mixed>  $sourceRow
     * @return array<string, mixed>
     */
    public function mapToProductAttributes(array $sourceRow, FeedMappingProfile $profile): array
    {
        $profile->loadMissing(['fieldMappings.canonicalField']);

        $attributes = [
            'raw_payload' => $sourceRow,
        ];
        $metadata = [];

        foreach ($profile->fieldMappings->sortBy('sort_order') as $mapping) {
            if ($mapping->mapping_action === 'skip') {
                continue;
            }

            $field = $mapping->canonicalField;

            if (! $field || ! $field->is_active) {
                continue;
            }

            $value = $this->transform($this->valueForMapping($sourceRow, $mapping), $mapping, $profile);

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
     * Return canonical field keys required by the profile but missing from the row.
     *
     * @param  array<string, mixed>  $canonicalRow
     * @return array<int, string>
     */
    public function missingRequiredFields(array $canonicalRow, FeedMappingProfile $profile): array
    {
        $profile->loadMissing(['fieldMappings.canonicalField']);

        return $profile->fieldMappings
            ->filter(fn (FeedFieldMapping $mapping): bool => $mapping->mapping_action !== 'skip'
                && ($mapping->is_required || (bool) $mapping->canonicalField?->is_required))
            ->map(fn (FeedFieldMapping $mapping): ?string => $mapping->canonicalField?->key)
            ->filter(fn (?string $key): bool => $key !== null && ! $this->hasValue($canonicalRow[$key] ?? null))
            ->values()
            ->all();
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
    private function valueForMapping(array $sourceRow, FeedFieldMapping|FeedProductFieldMapping $mapping): mixed
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

    private function transform(mixed $value, FeedFieldMapping|FeedProductFieldMapping $mapping, FeedMappingProfile|Feed $context): mixed
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
            'integer' => $this->normalizeInteger($value),
            'lowercase' => Str::lower((string) $value),
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

    private function normalizeDecimal(mixed $value, FeedMappingProfile|Feed $context): ?string
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
