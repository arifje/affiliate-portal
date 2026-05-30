<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'additional_image_urls' => 'array',
            'raw_payload' => 'array',
            'price' => 'decimal:2',
            'old_price' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'stock_quantity' => 'integer',
            'imported_at' => 'datetime',
            'published_at' => 'datetime',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'featured_sort_order' => 'integer',
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

    public function feed(): BelongsTo
    {
        return $this->belongsTo(Feed::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(Click::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(ProductView::class);
    }
}
