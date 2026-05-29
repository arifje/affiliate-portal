<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function feeds(): HasMany
    {
        return $this->hasMany(Feed::class);
    }

    public function feedMappingProfiles(): HasMany
    {
        return $this->hasMany(FeedMappingProfile::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(Click::class);
    }
}
