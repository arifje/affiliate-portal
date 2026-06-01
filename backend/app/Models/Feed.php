<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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
            'mapping' => 'array',
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

        static::saved(function (Feed $feed): void {
            $feed->ensureMappingProfile();
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

    public function mappingProfile(): BelongsTo
    {
        return $this->belongsTo(FeedMappingProfile::class, 'mapping_profile_id');
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

    public function ensureMappingProfile(): FeedMappingProfile
    {
        $profile = $this->mappingProfile;

        if ($profile && ($profile->is_template || $profile->feeds()->whereKeyNot($this->id)->exists())) {
            $profile = null;
        }

        if (! $profile) {
            $profile = FeedMappingProfile::query()->create([
                'site_id' => $this->site_id,
                'partner_id' => $this->partner_id,
                'name' => "{$this->name} mapping",
                'slug' => $this->mappingProfileSlug(),
                'provider' => $this->provider,
                'source_format' => $this->source_format ?: 'csv',
                'source_encoding' => $this->source_encoding ?: 'utf-8',
                'delimiter' => $this->delimiter,
                'enclosure' => $this->enclosure,
                'decimal_separator' => $this->decimal_separator ?: '.',
                'thousands_separator' => $this->thousands_separator,
                'currency' => $this->site?->currency ?: 'EUR',
                'locale' => $this->site?->locale ?: 'nl_NL',
                'timezone' => $this->site?->timezone ?: 'Europe/Amsterdam',
                'row_selector' => $this->row_selector,
                'first_row_is_header' => $this->first_row_is_header ?? true,
                'is_template' => false,
                'is_active' => $this->is_active,
            ]);

            $this->forceFill(['mapping_profile_id' => $profile->id])->saveQuietly();
        }

        $profile->forceFill([
            'site_id' => $this->site_id,
            'partner_id' => $this->partner_id,
            'name' => "{$this->name} mapping",
            'provider' => $this->provider,
            'source_format' => $this->source_format ?: 'csv',
            'source_encoding' => $this->source_encoding ?: 'utf-8',
            'delimiter' => $this->delimiter,
            'enclosure' => $this->enclosure,
            'decimal_separator' => $this->decimal_separator ?: '.',
            'thousands_separator' => $this->thousands_separator,
            'row_selector' => $this->row_selector ?: $profile->row_selector,
            'first_row_is_header' => $this->first_row_is_header ?? true,
            'is_active' => $this->is_active,
        ])->save();

        return $profile;
    }

    private function mappingProfileSlug(): string
    {
        return Str::of("feed-{$this->id}-{$this->slug}")
            ->slug()
            ->limit(255, '')
            ->toString();
    }
}
