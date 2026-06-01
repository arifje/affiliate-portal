<?php

namespace Tests\Feature\Demo;

use App\Models\Category;
use App\Models\Feed;
use App\Models\Partner;
use App\Models\Product;
use App\Models\Site;
use Database\Seeders\Demo\DemoHartslagmetersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoHartslagmetersSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_demo_products_for_the_hartslagmeters_site(): void
    {
        $this->seed(DemoHartslagmetersSeeder::class);
        $this->seed(DemoHartslagmetersSeeder::class);

        $site = Site::query()->where('slug', 'hartslagmeters_nl')->firstOrFail();
        $partner = Partner::query()->where('slug', 'demo-awin-sportshop')->firstOrFail();
        $feed = Feed::query()->where('site_id', $site->id)->where('slug', 'hartslagmeters-demo-awin-csv')->firstOrFail();

        $this->assertSame(5, Category::query()->where('site_id', $site->id)->count());
        $this->assertSame(12, Product::query()->where('site_id', $site->id)->count());
        $this->assertSame('awin', $partner->provider);
        $this->assertSame('file', $feed->source_type);
        $this->assertSame('csv', $feed->source_format);
        $this->assertSame(';', $feed->delimiter);
        $this->assertSame('external_id', $feed->unique_identifier_field);
        $this->assertNotEmpty($feed->sample_fields);
        $this->assertTrue(Storage::disk('local')->exists($feed->source_file_path));
        $this->assertGreaterThanOrEqual(20, $feed->productFieldMappings()->count());

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

    public function test_demo_reset_command_clears_affiliate_data_and_seeds_demo_catalog(): void
    {
        $this->seed(DemoHartslagmetersSeeder::class);

        Product::query()->firstOrFail()->delete();

        $this->artisan('demo:reset-affiliate-data')
            ->assertSuccessful();

        $site = Site::query()->where('slug', 'hartslagmeters_nl')->firstOrFail();

        $this->assertSame(1, Partner::query()->count());
        $this->assertSame(1, Feed::query()->count());
        $this->assertSame(12, Product::query()->where('site_id', $site->id)->count());
        $this->assertSame('demo-awin-sportshop', Partner::query()->firstOrFail()->slug);
        $this->assertSame('hartslagmeters-demo-awin-csv', Feed::query()->firstOrFail()->slug);
    }
}
