<?php

namespace Tests\Feature\Products;

use App\Models\Partner;
use App\Models\Product;
use App\Models\ProductView;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductViewTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_tracks_unique_daily_product_views_and_repeat_hits(): void
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

        Product::query()->create([
            'site_id' => $site->id,
            'partner_id' => $partner->id,
            'title' => 'PulsePeak Pro 5',
            'slug' => 'pulsepeak-pro-5',
            'affiliate_url' => 'https://example.com/out',
            'is_active' => true,
        ]);

        $payload = [
            'visitor_id' => 'visitor-123',
            'path' => '/preview/hartslagmeters_nl/products/pulsepeak-pro-5',
        ];

        $this->postJson('/api/sites/preview/hartslagmeters_nl/products/pulsepeak-pro-5/views', $payload)
            ->assertCreated()
            ->assertJson([
                'tracked' => true,
                'unique' => true,
            ]);

        $this->postJson('/api/sites/preview/hartslagmeters_nl/products/pulsepeak-pro-5/views', $payload)
            ->assertOk()
            ->assertJson([
                'tracked' => true,
                'unique' => false,
            ]);

        $this->postJson('/api/sites/preview/hartslagmeters_nl/products/pulsepeak-pro-5/views', [
            'visitor_id' => 'visitor-456',
        ])->assertCreated();

        $this->assertSame(2, ProductView::query()->count());
        $this->assertSame(1, ProductView::query()->where('view_count', 2)->count());
        $this->assertSame(1, ProductView::query()->where('view_count', 1)->count());
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
            'slug' => 'pulsepeak-pro-5',
            'affiliate_url' => 'https://example.com/out',
            'is_active' => true,
        ]);

        Product::query()->create([
            'site_id' => $site->id,
            'partner_id' => $partner->id,
            'title' => 'Inactive Product',
            'slug' => 'inactive-product',
            'affiliate_url' => 'https://example.com/out',
            'is_active' => false,
        ]);

        $this->postJson('/api/sites/preview/hartslagmeters_nl/products/pulsepeak-pro-5/views', [
            'visitor_id' => 'visitor-123',
        ])->assertNotFound();

        $this->postJson('/api/sites/preview/hartslagmeters_nl/products/inactive-product/views', [
            'visitor_id' => 'visitor-123',
        ])->assertNotFound();

        $this->assertSame(0, ProductView::query()->count());
    }
}
