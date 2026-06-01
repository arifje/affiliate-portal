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

    public function test_it_preserves_awin_productserve_metadata_fields(): void
    {
        $this->seed(CanonicalFieldSeeder::class);
        $this->seed(FeedMappingTemplateSeeder::class);

        $profile = FeedMappingProfile::query()
            ->where('provider', 'awin')
            ->where('slug', 'awin-legacy')
            ->firstOrFail();

        $row = [
            'aw_deep_link' => 'https://www.awin1.com/pclick.php?p=40817876568&a=697531&m=114444',
            'product_name' => 'Koelpasta Thermal Paste - CPU Koeler 3 Gram',
            'aw_product_id' => '40817876568',
            'merchant_product_id' => '48968614052167',
            'merchant_image_url' => 'https://cdn.shopify.com/product.jpg',
            'description' => 'Koelpasta voor CPU koelers.',
            'merchant_category' => 'Electronics, Circuit Boards & Components',
            'search_price' => '9.95',
            'merchant_name' => 'Earkings NL',
            'merchant_id' => '114444',
            'category_name' => 'Peripherals',
            'category_id' => '66',
            'aw_image_url' => 'https://images2.productserve.com/product.jpg',
            'currency' => 'EUR',
            'store_price' => '9.95',
            'delivery_cost' => '0',
            'merchant_deep_link' => 'https://earkings.nl/products/koelpasta',
            'language' => 'nl',
            'last_updated' => '2026-06-01 18:00:00',
            'display_price' => 'EUR9.95',
            'data_feed_id' => '100968',
            'brand_name' => 'Earkings',
            'brand_id' => '6804',
            'ean' => '8720000000001',
            'colour' => 'Grijs',
            'product_short_description' => 'CPU koelpasta.',
            'specifications' => '3 gram; hoge thermische conductiviteit',
            'condition' => 'new',
            'product_model' => 'Thermal Paste',
            'model_number' => 'TP-3G',
            'dimensions' => '3 gram',
            'keywords' => 'cpu, koelpasta',
            'promotional_text' => 'Super snel in huis',
            'product_type' => 'Thermal paste',
        ];

        $mapper = app(FeedRowMapper::class);

        $canonical = $mapper->map($row, $profile);
        $attributes = $mapper->mapToProductAttributes($row, $profile);

        $this->assertSame('40817876568', $canonical['external_id']);
        $this->assertSame('Electronics, Circuit Boards & Components', $canonical['category_path']);
        $this->assertSame('8720000000001', $attributes['ean']);
        $this->assertSame('Grijs', $attributes['color']);
        $this->assertSame('Thermal paste', $attributes['product_type']);
        $this->assertSame('https://cdn.shopify.com/product.jpg', $attributes['image_url']);
        $this->assertSame('9.95', $attributes['metadata']['pricing']['store_price']);
        $this->assertSame('EUR9.95', $attributes['metadata']['pricing']['display_price']);
        $this->assertSame('114444', $attributes['metadata']['network']['merchant_id']);
        $this->assertSame('Earkings NL', $attributes['metadata']['network']['merchant_name']);
        $this->assertSame('100968', $attributes['metadata']['network']['feed_id']);
        $this->assertSame('66', $attributes['metadata']['category']['network_id']);
        $this->assertSame('Peripherals', $attributes['metadata']['category']['network_name']);
        $this->assertSame('6804', $attributes['metadata']['brand']['id']);
        $this->assertSame('CPU koelpasta.', $attributes['metadata']['content']['short_description']);
        $this->assertSame('3 gram; hoge thermische conductiviteit', $attributes['metadata']['specifications']['raw']);
        $this->assertSame('Thermal Paste', $attributes['metadata']['specifications']['product_model']);
        $this->assertSame('TP-3G', $attributes['metadata']['specifications']['model_number']);
        $this->assertSame('3 gram', $attributes['metadata']['specifications']['dimensions']);
        $this->assertSame('nl', $attributes['metadata']['feed']['language']);
        $this->assertSame('2026-06-01 18:00:00', $attributes['metadata']['feed']['last_updated_at']);
    }
}
