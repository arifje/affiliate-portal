<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Site;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SitePreviewProductIndexController extends Controller
{
    public function __invoke(Request $request, Site $site): JsonResponse
    {
        $site->loadCount(['categories', 'feeds', 'products']);

        $search = trim((string) $request->query('q', ''));
        $categorySlugs = $this->queryList($request->query('categories'));
        $legacyCategorySlug = trim((string) $request->query('category', ''));
        $brandSlugs = $this->queryList($request->query('brands'));
        $legacyBrandSlug = trim((string) $request->query('brand', ''));
        $dealsOnly = $request->boolean('deals');
        $sort = (string) $request->query('sort', 'latest');

        if ($legacyCategorySlug !== '') {
            array_unshift($categorySlugs, $legacyCategorySlug);
        }

        if ($legacyBrandSlug !== '') {
            array_unshift($brandSlugs, $legacyBrandSlug);
        }

        $categorySlugs = array_values(array_unique($categorySlugs));
        $brandSlugs = array_values(array_unique($brandSlugs));
        $categories = $this->resolveCategories($site, $categorySlugs);
        $brands = $this->resolveBrands($site, $brandSlugs);

        $products = $site->products()
            ->with(['partner:id,name', 'category:id,name,slug'])
            ->where('is_active', true)
            ->when($categories->isNotEmpty(), fn (Builder $query) => $query->whereIn('category_id', $categories->pluck('id')))
            ->when($brands->isNotEmpty(), fn (Builder $query) => $query->whereIn('brand', $brands->all()))
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
                'title' => $this->title($site, $search, $categories, $brands, $dealsOnly),
                'search' => $search,
                'category' => $categories->count() === 1 ? $this->categoryPayload($categories->first()) : null,
                'categories' => $categories->map(fn (Category $category): array => $this->categoryPayload($category))->values(),
                'brand' => $brands->count() === 1 ? [
                    'name' => $brands->first(),
                    'slug' => Str::slug($brands->first()),
                ] : null,
                'brands' => $brands->map(fn (string $brand): array => [
                    'name' => $brand,
                    'slug' => Str::slug($brand),
                ])->values(),
                'deals' => $dealsOnly,
                'sort' => $sort,
            ],
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function queryList(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        $values = is_array($value) ? $value : [$value];

        return collect($values)
            ->flatMap(fn (mixed $item): array => explode(',', (string) $item))
            ->map(fn (string $item): string => trim($item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $categorySlugs
     * @return Collection<int, Category>
     */
    private function resolveCategories(Site $site, array $categorySlugs): Collection
    {
        if ($categorySlugs === []) {
            return collect();
        }

        $categories = $site->categories()
            ->whereIn('slug', $categorySlugs)
            ->where('is_active', true)
            ->get();

        if ($categories->count() !== count($categorySlugs)) {
            abort(404);
        }

        return collect($categorySlugs)
            ->map(fn (string $slug): ?Category => $categories->firstWhere('slug', $slug))
            ->filter()
            ->values();
    }

    /**
     * @param  array<int, string>  $brandSlugs
     * @return Collection<int, string>
     */
    private function resolveBrands(Site $site, array $brandSlugs): Collection
    {
        if ($brandSlugs === []) {
            return collect();
        }

        $brands = $site->products()
            ->where('is_active', true)
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->distinct()
            ->pluck('brand');

        $resolvedBrands = collect($brandSlugs)
            ->map(fn (string $slug): ?string => $brands->first(fn (string $brand): bool => Str::slug($brand) === $slug))
            ->filter()
            ->values();

        if ($resolvedBrands->count() !== count($brandSlugs)) {
            abort(404);
        }

        return $resolvedBrands;
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @param  Collection<int, string>  $brands
     */
    private function title(Site $site, string $search, Collection $categories, Collection $brands, bool $dealsOnly): string
    {
        if ($search !== '') {
            return 'Zoekresultaten voor "'.$search.'"';
        }

        if ($categories->count() === 1 && $brands->isEmpty() && ! $dealsOnly) {
            return $categories->first()->name;
        }

        if ($brands->count() === 1 && $categories->isEmpty() && ! $dealsOnly) {
            return $brands->first();
        }

        if ($categories->isNotEmpty() || $brands->isNotEmpty()) {
            return $dealsOnly ? 'Gefilterde aanbiedingen' : 'Gefilterde producten';
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
            ->get(['id', 'name', 'slug', 'description', 'hero_image'])
            ->map(fn (Category $category): array => [
                ...$this->categoryPayload($category),
                'products_count' => $category->products_count,
            ])
            ->values()
            ->all();
    }

    private function categoryPayload(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'hero_image' => $category->hero_image,
        ];
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
