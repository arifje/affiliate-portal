<?php

namespace App\Services\Feeds;

use App\Models\Category;
use App\Models\Feed;
use App\Models\FeedImportBatch;
use App\Models\FeedImportRowError;
use App\Models\Product;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class FeedImporter
{
    public function __construct(
        private readonly FeedStructureAnalyzer $analyzer,
        private readonly FeedRowMapper $mapper,
    ) {}

    public function import(Feed $feed): FeedImportBatch
    {
        $feed->loadMissing(['site', 'partner', 'productFieldMappings.canonicalField']);

        if ($feed->productFieldMappings->isEmpty()) {
            throw new RuntimeException('Create product field mappings before running this feed.');
        }

        $batch = FeedImportBatch::query()->create([
            'feed_id' => $feed->id,
            'status' => 'running',
            'source_url' => $this->sourceLabel($feed),
            'started_at' => now(),
        ]);

        $feed->forceFill([
            'last_import_status' => 'running',
            'last_import_started_at' => $batch->started_at,
            'last_import_finished_at' => null,
            'last_import_message' => null,
        ])->saveQuietly();

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;
        $seenProductIds = [];

        try {
            $rows = $this->analyzer->extractRows($feed);

            $batch->forceFill([
                'total_rows' => count($rows),
            ])->save();

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 1;

                try {
                    $canonical = $this->mapper->mapFeed($row, $feed);
                    $attributes = $this->withProductDefaults(
                        $feed,
                        $this->mapper->mapFeedToProductAttributes($row, $feed)
                    );
                    $identifier = $this->identifierValue($feed, $canonical, $attributes);
                    $missing = $this->mapper->missingRequiredFeedFields($canonical, $feed);

                    if ($identifier === null) {
                        $missing[] = $feed->unique_identifier_field ?: 'external_id';
                    }

                    if ($missing !== []) {
                        $failed++;
                        $this->recordRowError($batch, $rowNumber, $identifier, $row, $attributes, [
                            'Missing required fields: '.implode(', ', array_unique($missing)),
                        ]);

                        continue;
                    }

                    $existingProduct = $this->findExistingProduct($feed, $canonical, $attributes);

                    if ($existingProduct) {
                        if (! $feed->import_update_existing) {
                            $skipped++;
                            $seenProductIds[] = $existingProduct->id;

                            continue;
                        }

                        $attributes['slug'] = $existingProduct->slug ?: $this->uniqueSlug($feed, $attributes['title'], $existingProduct->id);
                        $existingProduct->forceFill($attributes)->save();
                        $seenProductIds[] = $existingProduct->id;
                        $updated++;

                        continue;
                    }

                    if (! $feed->import_create_new) {
                        $skipped++;

                        continue;
                    }

                    $attributes['slug'] = $this->uniqueSlug($feed, $attributes['title']);
                    $product = Product::query()->create($attributes);
                    $seenProductIds[] = $product->id;
                    $created++;
                } catch (Throwable $exception) {
                    $failed++;
                    $this->recordRowError($batch, $rowNumber, null, $row, [], [$exception->getMessage()]);
                }

                if (($rowNumber % 100) === 0) {
                    $batch->forceFill([
                        'processed_rows' => $rowNumber,
                        'created_rows' => $created,
                        'updated_rows' => $updated,
                        'skipped_rows' => $skipped,
                        'failed_rows' => $failed,
                    ])->save();
                }
            }

            $missingAffected = $this->applyMissingProductStrategy($feed, $seenProductIds);
            $status = $failed > 0 && ($created + $updated) === 0 ? 'failed' : 'completed';
            $message = __('admin.messages.feed_import_completed', [
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'failed' => $failed,
            ]);

            $batch->forceFill([
                'status' => $status,
                'processed_rows' => count($rows),
                'created_rows' => $created,
                'updated_rows' => $updated,
                'skipped_rows' => $skipped,
                'failed_rows' => $failed,
                'finished_at' => now(),
                'error_message' => $status === 'failed' ? $message : null,
                'metrics' => [
                    'missing_products_affected' => $missingAffected,
                ],
            ])->save();

            $feed->forceFill([
                'last_import_status' => $status,
                'last_import_finished_at' => $batch->finished_at,
                'last_import_message' => $message,
            ])->saveQuietly();

            return $batch;
        } catch (Throwable $exception) {
            $batch->forceFill([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => $exception->getMessage(),
            ])->save();

            $feed->forceFill([
                'last_import_status' => 'failed',
                'last_import_finished_at' => $batch->finished_at,
                'last_import_message' => $exception->getMessage(),
            ])->saveQuietly();

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function withProductDefaults(Feed $feed, array $attributes): array
    {
        $attributes['site_id'] = $feed->site_id;
        $attributes['partner_id'] = $feed->partner_id;
        $attributes['feed_id'] = $feed->id;
        $attributes['currency'] = strtoupper((string) ($attributes['currency'] ?? $feed->site?->currency ?? 'EUR'));
        $attributes['condition'] = $attributes['condition'] ?? 'new';
        $attributes['is_active'] = true;
        $attributes['imported_at'] = now();
        $attributes['category_id'] = $attributes['category_id'] ?? $this->categoryIdFor($feed, $attributes);

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $canonical
     * @param  array<string, mixed>  $attributes
     */
    private function findExistingProduct(Feed $feed, array $canonical, array $attributes): ?Product
    {
        [$column, $value] = $this->identifierColumnAndValue($feed, $canonical, $attributes);

        if ($column && $value !== null) {
            return Product::query()
                ->where('site_id', $feed->site_id)
                ->when($column === 'provider_product_id', fn ($query) => $query->where('partner_id', $feed->partner_id))
                ->where($column, $value)
                ->first();
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $canonical
     * @param  array<string, mixed>  $attributes
     */
    private function identifierValue(Feed $feed, array $canonical, array $attributes): ?string
    {
        return $this->identifierColumnAndValue($feed, $canonical, $attributes)[1];
    }

    /**
     * @param  array<string, mixed>  $canonical
     * @param  array<string, mixed>  $attributes
     * @return array{0: string|null, 1: string|null}
     */
    private function identifierColumnAndValue(Feed $feed, array $canonical, array $attributes): array
    {
        $uniqueKey = $feed->unique_identifier_field ?: 'external_id';
        $field = $feed->productFieldMappings
            ->first(fn ($mapping): bool => $mapping->canonicalField?->key === $uniqueKey)
            ?->canonicalField;

        $column = $field?->target_column;
        $value = $canonical[$uniqueKey] ?? null;

        if (! $column && array_key_exists($uniqueKey, $attributes)) {
            $column = $uniqueKey;
            $value = $attributes[$uniqueKey];
        }

        if (! $column) {
            $column = 'provider_product_id';
            $value = $attributes['provider_product_id'] ?? $value;
        }

        if (! is_scalar($value) || trim((string) $value) === '') {
            return [$column, null];
        }

        return [$column, trim((string) $value)];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function categoryIdFor(Feed $feed, array $attributes): ?int
    {
        $categoryName = $attributes['merchant_category']
            ?? Arr::last(array_filter(explode('>', (string) Arr::get($attributes, 'metadata.category.path'))));

        $categoryName = trim((string) $categoryName);

        if ($categoryName === '') {
            return null;
        }

        return Category::query()
            ->where('site_id', $feed->site_id)
            ->where(function ($query) use ($categoryName): void {
                $query->where('name', $categoryName)
                    ->orWhere('slug', Str::slug($categoryName));
            })
            ->value('id');
    }

    private function uniqueSlug(Feed $feed, string $title, ?int $ignoreProductId = null): string
    {
        $base = Str::slug($title) ?: 'product';
        $base = Str::limit($base, 220, '');
        $candidate = $base;
        $counter = 2;

        while (Product::query()
            ->where('site_id', $feed->site_id)
            ->where('slug', $candidate)
            ->when($ignoreProductId, fn ($query) => $query->whereKeyNot($ignoreProductId))
            ->exists()
        ) {
            $candidate = $base.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }

    /**
     * @param  array<int, int>  $seenProductIds
     */
    private function applyMissingProductStrategy(Feed $feed, array $seenProductIds): int
    {
        if ($seenProductIds === []) {
            return 0;
        }

        if (! $feed->import_delete_missing && ! $feed->import_disable_missing_for_site && ! $feed->import_disable_missing_globally) {
            return 0;
        }

        $query = Product::query()
            ->where('feed_id', $feed->id)
            ->whereNotIn('id', array_unique($seenProductIds));

        if ($feed->import_delete_missing) {
            return (int) $query->delete();
        }

        return (int) $query->update(['is_active' => false]);
    }

    /**
     * @param  array<string, mixed>  $sourcePayload
     * @param  array<string, mixed>  $mappedPayload
     * @param  array<int, string>  $errors
     */
    private function recordRowError(
        FeedImportBatch $batch,
        int $rowNumber,
        ?string $externalId,
        array $sourcePayload,
        array $mappedPayload,
        array $errors,
    ): void {
        FeedImportRowError::query()->create([
            'feed_import_batch_id' => $batch->id,
            'row_number' => $rowNumber,
            'external_id' => $externalId,
            'source_payload' => $sourcePayload,
            'mapped_payload' => $mappedPayload,
            'errors' => $errors,
            'created_at' => now(),
        ]);
    }

    private function sourceLabel(Feed $feed): ?string
    {
        if ($feed->source_type === 'file') {
            return $feed->source_file_path;
        }

        return $feed->source_url;
    }
}
