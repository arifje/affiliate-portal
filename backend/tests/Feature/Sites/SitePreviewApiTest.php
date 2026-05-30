<?php

namespace Tests\Feature\Sites;

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

        Product::query()->create([
            'site_id' => $site->id,
            'partner_id' => $partner->id,
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
            ->assertJsonPath('products.0.title', 'Running heart rate monitor');
    }
}
