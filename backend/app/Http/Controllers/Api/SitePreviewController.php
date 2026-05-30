<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Site;
use Illuminate\Http\JsonResponse;

class SitePreviewController extends Controller
{
    public function __invoke(Site $site): JsonResponse
    {
        $site->loadCount(['categories', 'feeds', 'products']);

        $products = $site->products()
            ->with(['partner:id,name', 'category:id,name'])
            ->where('is_active', true)
            ->latest('updated_at')
            ->limit(24)
            ->get([
                'id',
                'site_id',
                'partner_id',
                'category_id',
                'brand',
                'title',
                'slug',
                'image_url',
                'affiliate_url',
                'price',
                'old_price',
                'currency',
                'availability',
                'published_at',
            ]);

        $categories = $site->categories()
            ->withCount(['products' => fn ($query) => $query->where('is_active', true)])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(12)
            ->get(['id', 'name', 'slug', 'description']);

        return response()->json([
            'site' => [
                'id' => $site->id,
                'name' => $site->name,
                'slug' => $site->slug,
                'primary_domain' => $site->primary_domain,
                'domain_aliases' => $site->domain_aliases ?? [],
                'locale' => $site->locale,
                'currency' => $site->currency,
                'timezone' => $site->timezone,
                'theme' => $site->theme ?? [],
                'layout' => $site->layout ?? [],
                'settings' => $site->settings ?? [],
                'is_active' => $site->is_active,
                'counts' => [
                    'categories' => $site->categories_count,
                    'feeds' => $site->feeds_count,
                    'products' => $site->products_count,
                ],
            ],
            'products' => $products->map(fn ($product): array => [
                'id' => $product->id,
                'brand' => $product->brand,
                'title' => $product->title,
                'slug' => $product->slug,
                'image_url' => $product->image_url,
                'affiliate_url' => $product->affiliate_url,
                'price' => $product->price,
                'old_price' => $product->old_price,
                'currency' => $product->currency,
                'availability' => $product->availability,
                'published_at' => $product->published_at?->toISOString(),
                'partner' => $product->partner?->only(['id', 'name']),
                'category' => $product->category?->only(['id', 'name']),
            ])->values(),
            'categories' => $categories->map(fn ($category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'products_count' => $category->products_count,
            ])->values(),
        ]);
    }
}
