<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CanonicalField extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_searchable' => 'boolean',
            'is_filterable' => 'boolean',
            'is_variant' => 'boolean',
            'validation_rules' => 'array',
            'options' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function fieldMappings(): HasMany
    {
        return $this->hasMany(FeedFieldMapping::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearchable($query)
    {
        return $query->where('is_searchable', true);
    }

    public function scopeFilterable($query)
    {
        return $query->where('is_filterable', true);
    }
}
