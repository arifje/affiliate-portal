<?php

namespace Tests\Feature\Demo;

use App\Models\Category;
use App\Models\Feed;
use App\Models\Partner;
use App\Models\Product;
use App\Models\Site;
use Database\Seeders\Demo\DemoHartslagmetersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoHartslagmetersSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_demo_products_for_the_hartslagmeters_site(): void
    {
        $this->seed(DemoHartslagmetersSeeder::class);
        $this->seed(DemoHartslagmetersSeeder::class);

        $site = Site::query()->where('slug', 'hartslagmeters_nl')->firstOrFail();
        $partner = Partner::query()->where('slug', 'demo-sportshop')->firstOrFail();
        $feed = Feed::query()->where('site_id', $site->id)->where('slug', 'hartslagmeters-demo-feed')->firstOrFail();

        $this->assertSame(5, Category::query()->where('site_id', $site->id)->count());
        $this->assertSame(12, Product::query()->where('site_id', $site->id)->count());
        $this->assertSame('custom', $partner->provider);
        $this->assertSame('manual', $feed->source_type);

        $product = Product::query()
            ->where('site_id', $site->id)
            ->where('provider_product_id', 'DEMO-HRM-001')
            ->firstOrFail();

        $this->assertSame($partner->id, $product->partner_id);
        $this->assertSame($feed->id, $product->feed_id);
        $this->assertSame('PulsePeak Pro 5 Optische Hartslagmeter', $product->title);
        $this->assertSame('in_stock', $product->availability);
        $this->assertTrue($product->is_active);
        $this->assertTrue($product->metadata['demo']);
    }
}
