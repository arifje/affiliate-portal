<?php

namespace Tests\Feature\Sites;

use App\Models\Category;
use App\Models\Partner;
use App\Models\Product;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitePreviewProductIndexApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_products_filtered_by_category_brand_deals_and_search(): void
    {
        $site = Site::query()->create([
            'name' => 'Hartslagmeters',
            'slug' => 'hartslagmeters_nl',
            'primary_domain' => 'hartslagmeters.nl',
        ]);

        $partner = Partner::query()->create([
            'name' => 'Demo Sportshop',
            'slug' => 'demo-sportshop',
            'provider' => 'custom',
        ]);

        $watches = Category::query()->create([
            'site_id' => $site->id,
            'name' => 'Sporthorloges',
            'slug' => 'sporthorloges',
            'hero_image' => 'sites/hartslagmeters-nl/categories/sporthorloges/hero/header.jpg',
            'is_active' => true,
        ]);

        $straps = Category::query()->create([
            'site_id' => $site->id,
            'name' => 'Borstbanden',
            'slug' => 'borstbanden',
            'is_active' => true,
        ]);

        Product::query()->create([
            'site_id' => $site->id,
            'partner_id' => $partner->id,
            'category_id' => $watches->id,
            'brand' => 'HeartPilot',
            'title' => 'HeartPilot Sportwatch 42mm',
            'slug' => 'heartpilot-sportwatch-42mm',
            'affiliate_url' => 'https://example.com/heartpilot',
            'price' => 149.95,
            'old_price' => 179.95,
            'is_active' => true,
        ]);

        Product::query()->create([
            'site_id' => $site->id,
            'partner_id' => $partner->id,
            'category_id' => $straps->id,
            'brand' => 'VeloBeat',
            'title' => 'VeloBeat Chest Strap Duo',
            'slug' => 'velobeat-chest-strap-duo',
            'affiliate_url' => 'https://example.com/velobeat',
            'price' => 39.95,
            'is_active' => true,
        ]);

        $this->getJson('/api/sites/preview/hartslagmeters_nl/products?category=sporthorloges')
            ->assertOk()
            ->assertJsonCount(1, 'products')
            ->assertJsonPath('products.0.title', 'HeartPilot Sportwatch 42mm')
            ->assertJsonPath('meta.category.slug', 'sporthorloges')
            ->assertJsonPath('meta.category.hero_image', 'sites/hartslagmeters-nl/categories/sporthorloges/hero/header.jpg');

        $this->getJson('/api/sites/preview/hartslagmeters_nl/products?brand=heartpilot')
            ->assertOk()
            ->assertJsonCount(1, 'products')
            ->assertJsonPath('products.0.brand', 'HeartPilot')
            ->assertJsonPath('meta.brand.slug', 'heartpilot');

        $this->getJson('/api/sites/preview/hartslagmeters_nl/products?deals=1')
            ->assertOk()
            ->assertJsonCount(1, 'products')
            ->assertJsonPath('products.0.slug', 'heartpilot-sportwatch-42mm');

        $this->getJson('/api/sites/preview/hartslagmeters_nl/products?q=chest')
            ->assertOk()
            ->assertJsonCount(1, 'products')
            ->assertJsonPath('products.0.slug', 'velobeat-chest-strap-duo');

        $this->getJson('/api/sites/preview/hartslagmeters_nl/products?categories=sporthorloges,borstbanden')
            ->assertOk()
            ->assertJsonCount(2, 'products')
            ->assertJsonCount(2, 'meta.categories')
            ->assertJsonPath('meta.title', 'Gefilterde producten');

        $this->getJson('/api/sites/preview/hartslagmeters_nl/products?brands=heartpilot,velobeat')
            ->assertOk()
            ->assertJsonCount(2, 'products')
            ->assertJsonCount(2, 'meta.brands')
            ->assertJsonPath('meta.title', 'Gefilterde producten');

        $this->getJson('/api/sites/preview/hartslagmeters_nl/products?categories=borstbanden&brands=velobeat')
            ->assertOk()
            ->assertJsonCount(1, 'products')
            ->assertJsonPath('products.0.slug', 'velobeat-chest-strap-duo');
    }

    public function test_it_returns_not_found_for_unknown_brand_or_category(): void
    {
        $site = Site::query()->create([
            'name' => 'Hartslagmeters',
            'slug' => 'hartslagmeters_nl',
            'primary_domain' => 'hartslagmeters.nl',
        ]);

        $this->getJson('/api/sites/preview/'.$site->slug.'/products?category=unknown')
            ->assertNotFound();

        $this->getJson('/api/sites/preview/'.$site->slug.'/products?brand=unknown')
            ->assertNotFound();

        $this->getJson('/api/sites/preview/'.$site->slug.'/products?categories=unknown')
            ->assertNotFound();

        $this->getJson('/api/sites/preview/'.$site->slug.'/products?brands=unknown')
            ->assertNotFound();
    }
}
