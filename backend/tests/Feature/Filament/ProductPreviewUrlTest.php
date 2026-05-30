<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Partner;
use App\Models\Product;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPreviewUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_the_frontend_product_preview_url(): void
    {
        config(['app.frontend_url' => 'http://frontend.test']);

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
            'affiliate_url' => 'https://example.com/out',
        ]);

        $this->assertSame(
            'http://frontend.test/preview/hartslagmeters_nl/products/pulsepeak-pro-5',
            ProductResource::getPreviewUrl($product),
        );
    }
}
