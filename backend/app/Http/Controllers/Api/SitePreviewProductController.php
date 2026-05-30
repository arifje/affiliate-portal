<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Site;
use Illuminate\Http\JsonResponse;

class SitePreviewProductController extends Controller
{
    public function __invoke(Site $site, string $productSlug): JsonResponse
    {
        $site->loadCount(['categories', 'feeds', 'products']);

        $product = $site->products()
            ->with(['partner:id,name,website_url', 'category:id,name,slug'])
            ->where('slug', $productSlug)
            ->where('is_active', true)
            ->firstOrFail();

        $relatedProducts = $site->products()
            ->with(['partner:id,name', 'category:id,name'])
            ->whereKeyNot($product->id)
            ->where('is_active', true)
            ->when($product->category_id, fn ($query) => $query->where('category_id', $product->category_id))
            ->latest('updated_at')
            ->limit(4)
            ->get();

        if ($relatedProducts->count() < 4) {
            $fallbackProducts = $site->products()
                ->with(['partner:id,name', 'category:id,name'])
                ->whereKeyNot($product->id)
                ->where('is_active', true)
                ->whereNotIn('id', $relatedProducts->pluck('id'))
                ->latest('updated_at')
                ->limit(4 - $relatedProducts->count())
                ->get();

            $relatedProducts = $relatedProducts->concat($fallbackProducts);
        }

        return response()->json([
            'site' => $this->sitePayload($site),
            'product' => $this->productPayload($product),
            'related_products' => $relatedProducts
                ->map(fn (Product $relatedProduct): array => $this->productCardPayload($relatedProduct))
                ->values(),
        ]);
    }

    private function sitePayload(Site $site): array
    {
        return [
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
        ];
    }

    private function productPayload(Product $product): array
    {
        return [
            ...$this->productCardPayload($product),
            'description' => $product->description,
            'product_url' => $product->product_url,
            'tracking_url' => $product->tracking_url,
            'additional_image_urls' => $product->additional_image_urls ?? [],
            'condition' => $product->condition,
            'shipping_cost' => $product->shipping_cost,
            'stock_quantity' => $product->stock_quantity,
            'delivery_time' => $product->delivery_time,
            'color' => $product->color,
            'size' => $product->size,
            'gender' => $product->gender,
            'material' => $product->material,
            'pattern' => $product->pattern,
            'age_group' => $product->age_group,
            'merchant_category' => $product->merchant_category,
            'product_type' => $product->product_type,
            'metadata' => $product->metadata ?? [],
            'partner' => $product->partner ? [
                'id' => $product->partner->id,
                'name' => $product->partner->name,
                'website_url' => $product->partner->website_url,
            ] : null,
        ];
    }

    private function productCardPayload(Product $product): array
    {
        return [
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
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
                'slug' => $product->category->slug,
            ] : null,
            'partner' => $product->partner ? [
                'id' => $product->partner->id,
                'name' => $product->partner->name,
            ] : null,
        ];
    }
}
