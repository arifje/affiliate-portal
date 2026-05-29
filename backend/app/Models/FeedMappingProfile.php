<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeedMappingProfile extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'first_row_is_header' => 'boolean',
            'is_template' => 'boolean',
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function feeds(): HasMany
    {
        return $this->hasMany(Feed::class, 'mapping_profile_id');
    }

    public function fieldMappings(): HasMany
    {
        return $this->hasMany(FeedFieldMapping::class);
    }

    public function canonicalFields(): BelongsToMany
    {
        return $this->belongsToMany(CanonicalField::class, 'feed_field_mappings')
            ->withPivot([
                'source_field',
                'source_path',
                'fallback_fields',
                'default_value',
                'transform_type',
                'transform_config',
                'is_required',
                'sort_order',
            ])
            ->withTimestamps();
    }

    public function importBatches(): HasMany
    {
        return $this->hasMany(FeedImportBatch::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeTemplates($query)
    {
        return $query->where('is_template', true);
    }

    public function scopeProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }
}
