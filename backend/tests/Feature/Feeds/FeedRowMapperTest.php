<?php

namespace Tests\Feature\Feeds;

use App\Models\FeedMappingProfile;
use App\Services\Feeds\FeedRowMapper;
use Database\Seeders\FeedMapping\CanonicalFieldSeeder;
use Database\Seeders\FeedMapping\FeedMappingTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedRowMapperTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_maps_awin_rows_to_canonical_and_product_attributes(): void
    {
        $this->seed(CanonicalFieldSeeder::class);
        $this->seed(FeedMappingTemplateSeeder::class);

        $profile = FeedMappingProfile::query()
            ->where('provider', 'awin')
            ->where('slug', 'awin-legacy')
            ->firstOrFail();

        $row = [
            'aw_product_id' => 'AW-123',
            'merchant_product_id' => 'SKU-123',
            'product_name' => 'Blue running sweater',
            'description' => 'Soft training sweater',
            'merchant_product_category_path' => 'Sport > Sweaters',
            'merchant_category' => 'Sweaters',
            'aw_deep_link' => 'https://www.awin1.com/cread.php?awinmid=1',
            'merchant_deep_link' => 'https://merchant.test/products/blue-running-sweater',
            'merchant_image_url' => 'https://merchant.test/images/sweater.jpg',
            'search_price' => '39.95',
            'rrp_price' => '49.95',
            'currency' => 'EUR',
            'delivery_cost' => '4.95',
            'in_stock' => 'yes',
            'stock_quantity' => '12',
            'condition' => 'new',
            'brand_name' => 'Acme',
            'product_GTIN' => '8712345678901',
            'mpn' => 'ACME-SW-001',
            'colour' => 'Blue',
        ];

        $mapper = app(FeedRowMapper::class);

        $canonical = $mapper->map($row, $profile);
        $attributes = $mapper->mapToProductAttributes($row, $profile);

        $this->assertSame('AW-123', $canonical['external_id']);
        $this->assertSame('Blue running sweater', $canonical['title']);
        $this->assertSame('39.95', $canonical['price']);
        $this->assertSame('in_stock', $canonical['availability']);
        $this->assertSame(12, $canonical['stock_quantity']);
        $this->assertSame([], $mapper->missingRequiredFields($canonical, $profile));

        $this->assertSame('AW-123', $attributes['provider_product_id']);
        $this->assertSame('Blue running sweater', $attributes['title']);
        $this->assertSame('https://www.awin1.com/cread.php?awinmid=1', $attributes['affiliate_url']);
        $this->assertSame('39.95', $attributes['price']);
        $this->assertSame('Acme', $attributes['brand']);
        $this->assertSame('ACME-SW-001', $attributes['mpn']);
        $this->assertSame('Blue', $attributes['color']);
        $this->assertSame('Sport > Sweaters', $attributes['metadata']['category']['path']);
    }
}
