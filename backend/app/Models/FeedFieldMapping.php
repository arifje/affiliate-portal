<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedFieldMapping extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'fallback_fields' => 'array',
            'transform_config' => 'array',
            'is_required' => 'boolean',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(FeedMappingProfile::class, 'feed_mapping_profile_id');
    }

    public function canonicalField(): BelongsTo
    {
        return $this->belongsTo(CanonicalField::class);
    }
}
