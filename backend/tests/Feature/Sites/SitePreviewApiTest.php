<?php

namespace Tests\Feature\Sites;

use App\Models\Category;
use App\Models\Partner;
use App\Models\Product;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitePreviewApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_site_preview_data_by_slug(): void
    {
        $site = Site::query()->create([
            'name' => 'Hartslagmeters',
            'slug' => 'hartslagmeters-nl',
            'primary_domain' => 'hartslagmeters.nl',
            'domain_aliases' => ['www.hartslagmeters.nl'],
            'theme' => ['primary_color' => '#0f766e'],
            'layout' => ['home_template' => 'home_default'],
            'is_active' => false,
        ]);

        $partner = Partner::query()->create([
            'name' => 'Example Merchant',
            'slug' => 'example-merchant',
            'provider' => 'awin',
        ]);

        $category = Category::query()->create([
            'site_id' => $site->id,
            'name' => 'Sporthorloges',
            'slug' => 'sporthorloges',
            'description' => 'GPS watches and heart-rate wearables.',
            'sort_order' => 10,
        ]);

        Product::query()->create([
            'site_id' => $site->id,
            'partner_id' => $partner->id,
            'category_id' => $category->id,
            'provider_product_id' => 'HRM-1',
            'title' => 'Running heart rate monitor',
            'slug' => 'running-heart-rate-monitor',
            'image_url' => 'https://example.com/product.jpg',
            'affiliate_url' => 'https://example.com/out',
            'price' => 79.95,
            'currency' => 'EUR',
            'is_active' => true,
        ]);

        $this->getJson('/api/sites/preview/hartslagmeters-nl')
            ->assertOk()
            ->assertJsonPath('site.slug', 'hartslagmeters-nl')
            ->assertJsonPath('site.is_active', false)
            ->assertJsonPath('site.theme.primary_color', '#0f766e')
            ->assertJsonPath('products.0.title', 'Running heart rate monitor')
            ->assertJsonPath('categories.0.name', 'Sporthorloges')
            ->assertJsonPath('categories.0.products_count', 1);
    }

    public function test_it_returns_a_site_scoped_product_preview_page(): void
    {
        $site = Site::query()->create([
            'name' => 'Hartslagmeters',
            'slug' => 'hartslagmeters-nl',
            'primary_domain' => 'hartslagmeters.nl',
        ]);

        $otherSite = Site::query()->create([
            'name' => 'Maskers',
            'slug' => 'maskers-nl',
            'primary_domain' => 'maskers.nl',
        ]);

        $category = Category::query()->create([
            'site_id' => $site->id,
            'name' => 'Sporthorloges',
            'slug' => 'sporthorloges',
        ]);

        $partner = Partner::query()->create([
            'name' => 'Example Merchant',
            'slug' => 'example-merchant',
            'provider' => 'awin',
            'website_url' => 'https://example.com',
        ]);

        Product::query()->create([
            'site_id' => $site->id,
            'partner_id' => $partner->id,
            'category_id' => $category->id,
            'provider_product_id' => 'HRM-1',
            'title' => 'Running heart rate monitor',
            'slug' => 'running-heart-rate-monitor',
            'description' => 'Optical heart rate monitor with GPS support.',
            'image_url' => 'https://example.com/product.jpg',
            'affiliate_url' => 'https://example.com/out',
            'price' => 79.95,
            'currency' => 'EUR',
            'availability' => 'in_stock',
            'metadata' => ['highlights' => ['GPS support']],
            'is_active' => true,
        ]);

        Product::query()->create([
            'site_id' => $otherSite->id,
            'partner_id' => $partner->id,
            'provider_product_id' => 'MASK-1',
            'title' => 'Wrong site product',
            'slug' => 'running-heart-rate-monitor',
            'affiliate_url' => 'https://example.com/mask',
            'is_active' => true,
        ]);

        $this->getJson('/api/sites/preview/hartslagmeters-nl/products/running-heart-rate-monitor')
            ->assertOk()
            ->assertJsonPath('site.slug', 'hartslagmeters-nl')
            ->assertJsonPath('product.title', 'Running heart rate monitor')
            ->assertJsonPath('product.category.slug', 'sporthorloges')
            ->assertJsonPath('product.partner.website_url', 'https://example.com')
            ->assertJsonPath('product.metadata.highlights.0', 'GPS support');

        $this->getJson('/api/sites/preview/hartslagmeters-nl/products/unknown-product')
            ->assertNotFound();
    }
}
