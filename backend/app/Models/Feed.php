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
            'mapping' => 'array',
            'last_import_started_at' => 'datetime',
            'last_import_finished_at' => 'datetime',
            'import_create_new' => 'boolean',
            'import_update_existing' => 'boolean',
            'import_disable_missing_globally' => 'boolean',
            'import_disable_missing_for_site' => 'boolean',
            'import_delete_missing' => 'boolean',
            'import_update_search_indexes' => 'boolean',
            'is_active' => 'boolean',
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
}
