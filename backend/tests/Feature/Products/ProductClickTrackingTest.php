<?php

namespace Tests\Feature\Products;

use App\Models\Partner;
use App\Models\Product;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductClickTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_tracks_outbound_product_clicks_for_the_resolved_site(): void
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

        $product = Product::query()->create([
            'site_id' => $site->id,
            'partner_id' => $partner->id,
            'title' => 'PulsePeak Pro 5',
            'slug' => 'pulsepeak-pro-5',
            'affiliate_url' => 'https://merchant.test/pulsepeak-pro-5',
            'is_active' => true,
        ]);

        $this->postJson('/api/sites/preview/hartslagmeters_nl/products/pulsepeak-pro-5/clicks', [
            'visitor_id' => 'visitor-123',
            'path' => '/preview/hartslagmeters_nl/products/pulsepeak-pro-5',
            'target_url' => 'https://attacker.test/ignored',
        ])
            ->assertCreated()
            ->assertJsonPath('tracked', true);

        $this->assertDatabaseHas('clicks', [
            'site_id' => $site->id,
            'product_id' => $product->id,
            'partner_id' => $partner->id,
            'target_url' => 'https://merchant.test/pulsepeak-pro-5',
            'visitor_id' => 'visitor-123',
            'referer' => '/preview/hartslagmeters_nl/products/pulsepeak-pro-5',
        ]);
    }

    public function test_it_only_tracks_active_products_for_the_resolved_site(): void
    {
        $site = Site::query()->create([
            'name' => 'Hartslagmeters',
            'slug' => 'hartslagmeters_nl',
            'primary_domain' => 'hartslagmeters.nl',
        ]);

        $otherSite = Site::query()->create([
            'name' => 'Maskers',
            'slug' => 'maskers_nl',
            'primary_domain' => 'maskers.nl',
        ]);

        $partner = Partner::query()->create([
            'name' => 'Demo Sportshop',
            'slug' => 'demo-sportshop',
            'provider' => 'custom',
        ]);

        Product::query()->create([
            'site_id' => $otherSite->id,
            'partner_id' => $partner->id,
            'title' => 'Wrong Site Product',
            'slug' => 'shared-slug',
            'affiliate_url' => 'https://merchant.test/wrong-site',
            'is_active' => true,
        ]);

        Product::query()->create([
            'site_id' => $site->id,
            'partner_id' => $partner->id,
            'title' => 'Inactive Product',
            'slug' => 'inactive-product',
            'affiliate_url' => 'https://merchant.test/inactive',
            'is_active' => false,
        ]);

        $this->postJson('/api/sites/preview/hartslagmeters_nl/products/shared-slug/clicks')
            ->assertNotFound();

        $this->postJson('/api/sites/preview/hartslagmeters_nl/products/inactive-product/clicks')
            ->assertNotFound();

        $this->assertDatabaseCount('clicks', 0);
    }
}
