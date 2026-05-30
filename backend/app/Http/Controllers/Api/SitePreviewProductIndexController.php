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

class SitePreviewProductIndexController extends Controller
{
    public function __invoke(Request $request, Site $site): JsonResponse
    {
        $site->loadCount(['categories', 'feeds', 'products']);

        $search = trim((string) $request->query('q', ''));
        $categorySlug = trim((string) $request->query('category', ''));
        $brandSlug = trim((string) $request->query('brand', ''));
        $dealsOnly = $request->boolean('deals');
        $sort = (string) $request->query('sort', 'latest');

        $category = $categorySlug !== ''
            ? $site->categories()->where('slug', $categorySlug)->where('is_active', true)->firstOrFail()
            : null;

        $brand = $brandSlug !== ''
            ? $this->resolveBrand($site, $brandSlug)
            : null;

        if ($brandSlug !== '' && $brand === null) {
            abort(404);
        }

        $products = $site->products()
            ->with(['partner:id,name', 'category:id,name,slug'])
            ->where('is_active', true)
            ->when($category, fn (Builder $query) => $query->where('category_id', $category->id))
            ->when($brand, fn (Builder $query) => $query->where('brand', $brand))
            ->when($dealsOnly, fn (Builder $query) => $query
                ->whereNotNull('price')
                ->whereNotNull('old_price')
                ->whereColumn('old_price', '>', 'price'))
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('merchant_category', 'like', "%{$search}%")
                    ->orWhere('product_type', 'like', "%{$search}%");
            }))
            ->when($sort === 'price_asc', fn (Builder $query) => $query->orderBy('price'))
            ->when($sort === 'price_desc', fn (Builder $query) => $query->orderByDesc('price'))
            ->when(! in_array($sort, ['price_asc', 'price_desc'], true), fn (Builder $query) => $query->latest('updated_at'))
            ->limit(48)
            ->get();

        return response()->json([
            'site' => $this->sitePayload($site),
            'products' => $products->map(fn (Product $product): array => $this->productPayload($product))->values(),
            'categories' => $this->categoriesPayload($site),
            'brands' => $this->brandsPayload($site),
            'meta' => [
                'title' => $this->title($site, $search, $category, $brand, $dealsOnly),
                'search' => $search,
                'category' => $category?->only(['id', 'name', 'slug', 'description']),
                'brand' => $brand ? [
                    'name' => $brand,
                    'slug' => Str::slug($brand),
                ] : null,
                'deals' => $dealsOnly,
                'sort' => $sort,
            ],
        ]);
    }

    private function resolveBrand(Site $site, string $brandSlug): ?string
    {
        return $site->products()
            ->where('is_active', true)
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->distinct()
            ->pluck('brand')
            ->first(fn (string $brand): bool => Str::slug($brand) === $brandSlug);
    }

    private function title(Site $site, string $search, ?Category $category, ?string $brand, bool $dealsOnly): string
    {
        if ($search !== '') {
            return 'Zoekresultaten voor "'.$search.'"';
        }

        if ($category) {
            return $category->name;
        }

        if ($brand) {
            return $brand;
        }

        if ($dealsOnly) {
            return 'Aanbiedingen';
        }

        return 'Alle producten op '.$site->name;
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
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
                'slug' => $product->category->slug,
            ] : null,
        ];
    }

    private function categoriesPayload(Site $site): array
    {
        return $site->categories()
            ->withCount(['products' => fn (Builder $query) => $query->where('is_active', true)])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description'])
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'products_count' => $category->products_count,
            ])
            ->values()
            ->all();
    }

    private function brandsPayload(Site $site): array
    {
        return $site->products()
            ->where('is_active', true)
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->select('brand')
            ->selectRaw('count(*) as products_count')
            ->groupBy('brand')
            ->orderBy('brand')
            ->get()
            ->map(fn (Product $product): array => [
                'name' => $product->brand,
                'slug' => Str::slug((string) $product->brand),
                'products_count' => $product->products_count,
            ])
            ->values()
            ->all();
    }
}
