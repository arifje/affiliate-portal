<?php

namespace Tests\Feature\Feeds;

use App\Models\CanonicalField;
use App\Models\Feed;
use App\Models\Partner;
use App\Models\Site;
use App\Services\Feeds\FeedRowMapper;
use Database\Seeders\FeedMapping\CanonicalFieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedRowMapperTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_maps_awin_rows_to_canonical_and_product_attributes(): void
    {
        $this->seed(CanonicalFieldSeeder::class);

        $feed = $this->feedForTemplate('awin', 'awin-legacy');

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

        $canonical = $mapper->mapFeed($row, $feed);
        $attributes = $mapper->mapFeedToProductAttributes($row, $feed);

        $this->assertSame('AW-123', $canonical['external_id']);
        $this->assertSame('Blue running sweater', $canonical['title']);
        $this->assertSame('39.95', $canonical['price']);
        $this->assertSame('in_stock', $canonical['availability']);
        $this->assertSame(12, $canonical['stock_quantity']);
        $this->assertSame([], $mapper->missingRequiredFeedFields($canonical, $feed));

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

        $feed = $this->feedForTemplate('awin', 'awin-legacy');

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

        $canonical = $mapper->mapFeed($row, $feed);
        $attributes = $mapper->mapFeedToProductAttributes($row, $feed);

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

    public function test_it_maps_daisycon_standard_productfeed_fields(): void
    {
        $this->seed(CanonicalFieldSeeder::class);

        $feed = $this->feedForTemplate('daisycon', 'daisycon-standard');

        $row = [
            'sku' => 'DCO-HRM-001',
            'title' => 'Daisycon Hartslagmeter Pro',
            'description' => 'Een betrouwbare hartslagmeter voor dagelijks sporten.',
            'link' => 'https://merchant.test/daisycon/hrm-pro',
            'price' => '129.95',
            'default image' => 'https://merchant.test/images/hrm-pro.jpg',
            'detail_image_1' => 'https://merchant.test/images/hrm-pro-side.jpg',
            'detail image 2' => 'https://merchant.test/images/hrm-pro-box.jpg',
            'additional_costs' => '1.25',
            'brand' => 'PulseCraft',
            'brand_logo' => 'https://merchant.test/brand/pulsecraft.svg',
            'category' => 'Hartslagmeters',
            'category_path' => 'Sport > Hartslagmeters > Polsmeters',
            'color_primary' => 'Black',
            'condition' => 'new',
            'delivery_description' => 'Gratis bezorging vanaf morgen.',
            'delivery_time' => '1-2 werkdagen',
            'ean' => '8720000000999',
            'gender_target' => 'unisex',
            'google_category_id' => '2271',
            'in_stock' => 'true',
            'in_stock_amount' => '8',
            'keywords' => 'hartslagmeter, sporthorloge, polsmeter',
            'model' => 'HRM Pro',
            'price_old' => '149.95',
            'price_shipping' => '0',
            'priority' => '7',
            'size' => 'one size',
            'size_description' => 'Verstelbare polsband',
            'terms_conditions' => 'Alleen online geldig.',
            'unit_price_base_measure' => '1',
            'unit_price_measure' => '100',
            'unit_price_measure_unit' => 'g',
            'currency' => 'EUR',
        ];

        $mapper = app(FeedRowMapper::class);

        $canonical = $mapper->mapFeed($row, $feed);
        $attributes = $mapper->mapFeedToProductAttributes($row, $feed);

        $this->assertSame('DCO-HRM-001', $canonical['external_id']);
        $this->assertSame('Daisycon Hartslagmeter Pro', $canonical['title']);
        $this->assertSame('129.95', $canonical['price']);
        $this->assertSame('in_stock', $canonical['availability']);
        $this->assertSame(8, $canonical['stock_quantity']);
        $this->assertSame([], $mapper->missingRequiredFeedFields($canonical, $feed));

        $this->assertSame('DCO-HRM-001', $attributes['provider_product_id']);
        $this->assertSame('https://merchant.test/daisycon/hrm-pro', $attributes['affiliate_url']);
        $this->assertSame('https://merchant.test/images/hrm-pro.jpg', $attributes['image_url']);
        $this->assertSame([
            'https://merchant.test/images/hrm-pro-side.jpg',
            'https://merchant.test/images/hrm-pro-box.jpg',
        ], $attributes['additional_image_urls']);
        $this->assertSame('129.95', $attributes['price']);
        $this->assertSame('149.95', $attributes['old_price']);
        $this->assertSame('0.00', $attributes['shipping_cost']);
        $this->assertSame('PulseCraft', $attributes['brand']);
        $this->assertSame('Black', $attributes['color']);
        $this->assertSame('new', $attributes['condition']);
        $this->assertSame('1-2 werkdagen', $attributes['delivery_time']);
        $this->assertSame('8720000000999', $attributes['ean']);
        $this->assertSame('unisex', $attributes['gender']);
        $this->assertSame('one size', $attributes['size']);
        $this->assertSame('Sport > Hartslagmeters > Polsmeters', $attributes['metadata']['category']['path']);
        $this->assertSame('1.25', $attributes['metadata']['pricing']['additional_costs']);
        $this->assertSame('1.00', $attributes['metadata']['pricing']['unit_price']['base_measure']);
        $this->assertSame('100.00', $attributes['metadata']['pricing']['unit_price']['measure']);
        $this->assertSame('g', $attributes['metadata']['pricing']['unit_price']['measure_unit']);
        $this->assertSame('https://merchant.test/brand/pulsecraft.svg', $attributes['metadata']['brand']['logo_url']);
        $this->assertSame('Gratis bezorging vanaf morgen.', $attributes['metadata']['delivery']['description']);
        $this->assertSame(2271, $attributes['metadata']['classification']['google_category_id']);
        $this->assertSame('hartslagmeter, sporthorloge, polsmeter', $attributes['metadata']['content']['keywords']);
        $this->assertSame('HRM Pro', $attributes['metadata']['specifications']['product_model']);
        $this->assertSame(7, $attributes['metadata']['feed']['priority']);
        $this->assertSame('Verstelbare polsband', $attributes['metadata']['variants']['size_description']);
        $this->assertSame('Alleen online geldig.', $attributes['metadata']['compliance']['terms_conditions']);
    }

    private function feedForTemplate(string $provider, string $slug): Feed
    {
        $template = collect(config('feed-mapping.provider_templates', []))
            ->first(fn (array $template): bool => $template['provider'] === $provider && $template['slug'] === $slug);

        $this->assertNotNull($template, "Missing feed template [{$provider}:{$slug}].");

        $site = Site::query()->create([
            'name' => 'Test site '.uniqid(),
            'slug' => 'test-site-'.uniqid(),
            'primary_domain' => uniqid('test-', true).'.test',
            'locale' => 'nl_NL',
            'currency' => $template['currency'] ?? 'EUR',
            'timezone' => 'Europe/Amsterdam',
            'is_active' => true,
        ]);

        $partner = Partner::query()->create([
            'name' => ucfirst($provider).' test partner '.uniqid(),
            'slug' => $provider.'-partner-'.uniqid(),
            'provider' => $provider,
            'is_active' => true,
        ]);

        $feed = Feed::query()->create([
            'site_id' => $site->id,
            'partner_id' => $partner->id,
            'name' => $template['name'],
            'slug' => $slug.'-'.uniqid(),
            'provider' => $provider,
            'source_type' => 'file',
            'source_format' => $template['source_format'] ?? 'csv',
            'source_encoding' => 'utf-8',
            'delimiter' => $template['delimiter'] ?? ',',
            'enclosure' => $template['enclosure'] ?? '"',
            'decimal_separator' => $template['decimal_separator'] ?? '.',
            'thousands_separator' => $template['thousands_separator'] ?? null,
            'row_selector' => $template['row_selector'] ?? null,
            'first_row_is_header' => true,
            'unique_identifier_field' => 'external_id',
            'import_create_new' => true,
            'import_update_existing' => true,
            'import_update_search_indexes' => true,
            'is_active' => true,
        ]);

        foreach ($template['mappings'] as $key => $mapping) {
            $field = CanonicalField::query()->where('key', $key)->firstOrFail();

            $feed->productFieldMappings()->create([
                'canonical_field_id' => $field->id,
                'mapping_action' => 'map',
                'source_field' => $mapping['source_field'] ?? null,
                'source_path' => $mapping['source_field'] ?? null,
                'source_sample' => null,
                'fallback_fields' => $mapping['fallback_fields'] ?? [],
                'default_value' => $mapping['default_value'] ?? null,
                'transform_type' => $mapping['transform_type'] ?? $this->defaultTransformForField($field),
                'transform_config' => $mapping['transform_config'] ?? null,
                'is_required' => $field->is_required,
                'sort_order' => $field->sort_order,
            ]);
        }

        return $feed->refresh()->load('productFieldMappings.canonicalField');
    }

    private function defaultTransformForField(CanonicalField $field): string
    {
        return match ($field->data_type) {
            'boolean' => 'boolean',
            'decimal' => 'decimal',
            'integer' => 'integer',
            'array' => 'array',
            'url' => 'url',
            default => 'copy',
        };
    }
}
