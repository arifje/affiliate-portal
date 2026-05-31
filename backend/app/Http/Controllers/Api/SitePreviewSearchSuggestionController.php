<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Site;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SitePreviewSearchSuggestionController extends Controller
{
    public function __invoke(Request $request, Site $site): JsonResponse
    {
        $search = Str::of((string) $request->query('q', ''))
            ->squish()
            ->limit(100, '')
            ->toString();

        if (Str::length($search) < 2) {
            return response()->json([
                'query' => $search,
                'suggestions' => [],
            ]);
        }

        return response()->json([
            'query' => $search,
            'suggestions' => [
                ...$this->productSuggestions($site, $search),
                ...$this->categorySuggestions($site, $search),
                ...$this->brandSuggestions($site, $search),
            ],
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function productSuggestions(Site $site, string $search): array
    {
        return $site->products()
            ->with(['category:id,name,slug'])
            ->where('is_active', true)
            ->where(function (Builder $query) use ($search): void {
                $query
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('merchant_category', 'like', "%{$search}%")
                    ->orWhere('product_type', 'like', "%{$search}%");
            })
            ->latest('updated_at')
            ->limit(6)
            ->get([
                'id',
                'site_id',
                'category_id',
                'brand',
                'title',
                'slug',
                'image_url',
                'price',
                'currency',
            ])
            ->map(fn (Product $product): array => [
                'type' => 'product',
                'title' => $product->title,
                'subtitle' => collect([$product->brand, $product->category?->name])
                    ->filter()
                    ->implode(' - '),
                'slug' => $product->slug,
                'image_url' => $product->image_url,
                'price' => $product->price,
                'currency' => $product->currency,
                'products_count' => null,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function categorySuggestions(Site $site, string $search): array
    {
        return $site->categories()
            ->withCount(['products' => fn (Builder $query) => $query->where('is_active', true)])
            ->where('is_active', true)
            ->where(function (Builder $query) use ($search): void {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(4)
            ->get(['id', 'site_id', 'name', 'slug', 'description'])
            ->map(fn (Category $category): array => [
                'type' => 'category',
                'title' => $category->name,
                'subtitle' => $category->products_count.' producten',
                'slug' => $category->slug,
                'image_url' => null,
                'price' => null,
                'currency' => null,
                'products_count' => $category->products_count,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function brandSuggestions(Site $site, string $search): array
    {
        return $site->products()
            ->where('is_active', true)
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->where('brand', 'like', "%{$search}%")
            ->select('brand')
            ->selectRaw('count(*) as products_count')
            ->groupBy('brand')
            ->orderByDesc('products_count')
            ->orderBy('brand')
            ->limit(4)
            ->get()
            ->map(fn (Product $product): array => [
                'type' => 'brand',
                'title' => $product->brand,
                'subtitle' => $product->products_count.' producten',
                'slug' => Str::slug((string) $product->brand),
                'image_url' => null,
                'price' => null,
                'currency' => null,
                'products_count' => $product->products_count,
            ])
            ->values()
            ->all();
    }
}
