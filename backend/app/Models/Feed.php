<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Feed extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'request_headers' => 'encrypted:array',
            'request_query_params' => 'encrypted:array',
            'source_file_original_name' => 'array',
            'available_elements' => 'array',
            'sample_fields' => 'array',
            'sample_payload' => 'array',
            'mapping' => 'array',
            'last_analyzed_at' => 'datetime',
            'last_import_started_at' => 'datetime',
            'last_import_finished_at' => 'datetime',
            'first_row_is_header' => 'boolean',
            'import_create_new' => 'boolean',
            'import_update_existing' => 'boolean',
            'import_disable_missing_globally' => 'boolean',
            'import_disable_missing_for_site' => 'boolean',
            'import_delete_missing' => 'boolean',
            'import_update_search_indexes' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Feed $feed): void {
            if (! $feed->partner_id) {
                return;
            }

            $provider = Partner::query()
                ->whereKey($feed->partner_id)
                ->value('provider');

            if ($provider) {
                $feed->provider = $provider;
            }
        });
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function productFieldMappings(): HasMany
    {
        return $this->hasMany(FeedProductFieldMapping::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function importBatches(): HasMany
    {
        return $this->hasMany(FeedImportBatch::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(Click::class);
    }

    public function isImportDue(): bool
    {
        $schedule = strtolower(trim((string) $this->schedule));

        if ($schedule === '' || $schedule === 'manual') {
            return false;
        }

        $lastRun = $this->last_import_started_at ?: $this->last_import_finished_at;

        if (! $lastRun) {
            return true;
        }

        return match ($schedule) {
            'hourly' => $lastRun->lte(now()->subHour()),
            'daily' => $lastRun->lte(now()->subDay()),
            'weekly' => $lastRun->lte(now()->subWeek()),
            default => false,
        };
    }
}
