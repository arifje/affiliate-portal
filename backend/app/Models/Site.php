<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'domain_aliases' => 'array',
            'theme' => 'array',
            'layout' => 'array',
            'settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
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

    public function productViews(): HasMany
    {
        return $this->hasMany(ProductView::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(Click::class);
    }
}
