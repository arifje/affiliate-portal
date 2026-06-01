<?php

namespace Tests\Feature\Feeds;

use App\Models\CanonicalField;
use App\Models\Feed;
use App\Models\Partner;
use App\Models\Product;
use App\Models\Site;
use App\Services\Feeds\FeedImporter;
use Database\Seeders\FeedMapping\CanonicalFieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FeedImporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_and_updates_products_from_uploaded_csv_feed(): void
    {
        Storage::fake('local');
        $this->seed(CanonicalFieldSeeder::class);

        $site = Site::query()->create([
            'name' => 'Hartslagmeters',
            'slug' => 'hartslagmeters_nl',
            'primary_domain' => 'hartslagmeters.test',
            'currency' => 'EUR',
            'is_active' => true,
        ]);
        $partner = Partner::query()->create([
            'name' => 'Awin Advertiser',
            'slug' => 'awin-advertiser',
            'provider' => 'awin',
            'is_active' => true,
        ]);
        $path = 'feeds/site-'.$site->id.'/awin.csv';

        Storage::disk('local')->put($path, implode("\n", [
            'aw_product_id;product_name;aw_deep_link;search_price;brand_name',
            'AW-1;Pulse watch;https://awin.test/click/1;129,95;PulsePeak',
        ]));

        $feed = Feed::query()->create([
            'site_id' => $site->id,
            'partner_id' => $partner->id,
            'name' => 'Awin CSV',
            'slug' => 'awin-csv',
            'provider' => 'awin',
            'source_type' => 'file',
            'source_format' => 'csv',
            'source_file_path' => $path,
            'delimiter' => ';',
            'decimal_separator' => ',',
            'unique_identifier_field' => 'external_id',
            'import_create_new' => true,
            'import_update_existing' => true,
            'is_active' => true,
        ]);

        $this->mapField($feed, 'external_id', 'aw_product_id');
        $this->mapField($feed, 'title', 'product_name');
        $this->mapField($feed, 'affiliate_url', 'aw_deep_link');
        $this->mapField($feed, 'price', 'search_price', 'money');
        $this->mapField($feed, 'brand', 'brand_name');

        $batch = app(FeedImporter::class)->import($feed);

        $this->assertSame('completed', $batch->status);
        $this->assertSame(1, $batch->created_rows);
        $this->assertDatabaseHas('products', [
            'site_id' => $site->id,
            'partner_id' => $partner->id,
            'provider_product_id' => 'AW-1',
            'title' => 'Pulse watch',
            'brand' => 'PulsePeak',
        ]);
        $this->assertSame('129.95', Product::query()->firstOrFail()->price);

        Storage::disk('local')->put($path, implode("\n", [
            'aw_product_id;product_name;aw_deep_link;search_price;brand_name',
            'AW-1;Pulse watch updated;https://awin.test/click/1;119,95;PulsePeak',
        ]));

        $secondBatch = app(FeedImporter::class)->import($feed->refresh());

        $this->assertSame(0, $secondBatch->created_rows);
        $this->assertSame(1, $secondBatch->updated_rows);
        $this->assertSame(1, Product::query()->count());
        $this->assertDatabaseHas('products', [
            'provider_product_id' => 'AW-1',
            'title' => 'Pulse watch updated',
        ]);
    }

    public function test_it_imports_nested_tradetracker_json_feed(): void
    {
        Storage::fake('local');
        $this->seed(CanonicalFieldSeeder::class);

        $site = Site::query()->create([
            'name' => 'Keukenapparaten',
            'slug' => 'keukenapparaten_nl',
            'primary_domain' => 'keukenapparaten.test',
            'currency' => 'EUR',
            'is_active' => true,
        ]);
        $partner = Partner::query()->create([
            'name' => 'TradeTracker Advertiser',
            'slug' => 'tradetracker-advertiser',
            'provider' => 'tradetracker',
            'is_active' => true,
        ]);
        $path = 'feeds/site-'.$site->id.'/tradetracker.json';

        Storage::disk('local')->put($path, json_encode([
            'products' => [
                [
                    'ID' => '1183372',
                    'campaignID' => 904,
                    'name' => 'Whirlpool CRISP plaat AVM290 magnetron',
                    'price' => [
                        'currency' => 'EUR',
                        'amount' => 27.989999999999998,
                    ],
                    'URL' => 'https://www.alternate.nl/tt/?tt=904_1594453_373017_&r=https%3A%2F%2Fwww.alternate.nl%2Fproduct%2F1183372',
                    'images' => [
                        'https://p.skitz.eu/750/725387.jpg',
                    ],
                    'description' => 'CRISP plaat voor Whirlpool/Bauknecht.',
                    'categories' => [
                        'Magnetron' => 'Magnetron',
                    ],
                    'properties' => [
                        'availability' => ['niet op voorraad'],
                        'deliveryCosts' => ['6.95'],
                        'MPN' => ['AVM290'],
                        'brand' => ['Whirlpool'],
                        'condition' => ['Nieuw'],
                        'GTIN' => ['8015250040258'],
                        'weight' => ['616 gram'],
                        'deliveryTime' => ['Niet op voorraad, geen informatie beschikbaar'],
                        'Adviesprijs' => ['27.99'],
                        'availability_date' => [''],
                    ],
                    'variations' => [],
                ],
                [
                    'ID' => '1232842',
                    'campaignID' => 904,
                    'name' => 'Inventum blender',
                    'price' => [
                        'currency' => 'EUR',
                        'amount' => 39.95,
                    ],
                    'URL' => 'https://www.alternate.nl/tt/?tt=904_1594453_373017_&r=https%3A%2F%2Fwww.alternate.nl%2Fproduct%2F1232842',
                    'images' => [
                        'https://p.skitz.eu/750/1232842.jpg',
                    ],
                    'description' => 'Blender voor dagelijks gebruik.',
                    'categories' => [
                        'Blender' => 'Blender',
                    ],
                    'properties' => [
                        'availability' => ['op voorraad'],
                        'deliveryCosts' => ['0'],
                        'MPN' => ['BL-123'],
                        'brand' => ['Inventum'],
                        'condition' => ['Nieuw'],
                        'GTIN' => ['8712876100000'],
                        'weight' => ['1.4 kg'],
                        'deliveryTime' => ['Morgen in huis'],
                        'Adviesprijs' => ['49.95'],
                        'availability_date' => [''],
                    ],
                    'variations' => [],
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $feed = Feed::query()->create([
            'site_id' => $site->id,
            'partner_id' => $partner->id,
            'name' => 'TradeTracker JSON',
            'slug' => 'tradetracker-json',
            'provider' => 'tradetracker',
            'source_type' => 'file',
            'source_format' => 'json',
            'source_file_path' => $path,
            'row_selector' => 'products',
            'decimal_separator' => '.',
            'unique_identifier_field' => 'external_id',
            'import_create_new' => true,
            'import_update_existing' => true,
            'is_active' => true,
        ]);

        $this->mapField($feed, 'external_id', 'ID');
        $this->mapField($feed, 'network_campaign_id', 'campaignID');
        $this->mapField($feed, 'title', 'name');
        $this->mapField($feed, 'description', 'description');
        $this->mapField($feed, 'merchant_category', 'categories', 'first_value');
        $this->mapField($feed, 'product_type', 'categories', 'first_value');
        $this->mapField($feed, 'affiliate_url', 'URL');
        $this->mapField($feed, 'tracking_url', 'URL');
        $this->mapField($feed, 'image_url', 'images.0');
        $this->mapField($feed, 'additional_image_urls', 'images', 'array');
        $this->mapField($feed, 'price', 'price.amount', 'money');
        $this->mapField($feed, 'old_price', 'properties.Adviesprijs.0', 'money');
        $this->mapField($feed, 'currency', 'price.currency');
        $this->mapField($feed, 'shipping_cost', 'properties.deliveryCosts.0', 'money');
        $this->mapField($feed, 'availability', 'properties.availability.0', 'availability');
        $this->mapField($feed, 'delivery_time', 'properties.deliveryTime.0');
        $this->mapField($feed, 'condition', 'properties.condition.0');
        $this->mapField($feed, 'brand', 'properties.brand.0');
        $this->mapField($feed, 'gtin', 'properties.GTIN.0');
        $this->mapField($feed, 'mpn', 'properties.MPN.0');
        $this->mapField($feed, 'weight', 'properties.weight.0');

        $batch = app(FeedImporter::class)->import($feed);

        $this->assertSame('completed', $batch->status);
        $this->assertSame(2, $batch->created_rows);
        $this->assertSame(2, Product::query()->count());

        $product = Product::query()
            ->where('provider_product_id', '1183372')
            ->firstOrFail();

        $this->assertSame('Whirlpool CRISP plaat AVM290 magnetron', $product->title);
        $this->assertSame('Whirlpool', $product->brand);
        $this->assertSame('8015250040258', $product->ean);
        $this->assertSame('AVM290', $product->mpn);
        $this->assertSame('27.99', $product->price);
        $this->assertSame('27.99', $product->old_price);
        $this->assertSame('6.95', $product->shipping_cost);
        $this->assertSame('out_of_stock', $product->availability);
        $this->assertSame('Magnetron', $product->merchant_category);
        $this->assertSame('Magnetron', $product->product_type);
        $this->assertSame('https://p.skitz.eu/750/725387.jpg', $product->image_url);
        $this->assertSame(['https://p.skitz.eu/750/725387.jpg'], $product->additional_image_urls);
        $this->assertSame(904, $product->metadata['network']['campaign_id']);
        $this->assertSame('616 gram', $product->metadata['specifications']['weight']);
    }

    public function test_it_imports_tradetracker_xml_feed_with_named_properties(): void
    {
        Storage::fake('local');
        $this->seed(CanonicalFieldSeeder::class);

        $site = Site::query()->create([
            'name' => 'Audio',
            'slug' => 'audio_nl',
            'primary_domain' => 'audio.test',
            'currency' => 'EUR',
            'is_active' => true,
        ]);
        $partner = Partner::query()->create([
            'name' => 'TradeTracker Audio',
            'slug' => 'tradetracker-audio',
            'provider' => 'tradetracker',
            'is_active' => true,
        ]);
        $path = 'feeds/site-'.$site->id.'/tradetracker.xml';

        Storage::disk('local')->put($path, <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<products>
  <product ID="12b7e85410114ba3b61cc02ee2ed5c19dcb22ac6">
    <campaignID>20790</campaignID>
    <name>Audio Dynavox Black Line Cinchkabel Stereo 1,5 meter</name>
    <price currency="EUR">80.25</price>
    <URL>https://www.audioshop.nl/website/Includes/TradeTracker/index.php?tt=20790_1687778_373017_&amp;r=https%3A%2F%2Fwww.audioshop.nl%2Fproduct</URL>
    <images>
      <image>https://www.audioshop.nl/image-1.jpg</image>
      <image>https://www.audioshop.nl/image-2.jpg</image>
    </images>
    <description><![CDATA[Dynavox Black Line-serie]]></description>
    <categories/>
    <properties>
      <property name="EAN"><value>4250019131523</value></property>
      <property name="categoryPath"><value>Accessoires</value><value>Outlet</value></property>
      <property name="MPN"><value>3586</value></property>
      <property name="fromPrice"><value>107.00</value></property>
      <property name="deliveryTime"><value>Direct</value></property>
      <property name="deliveryCosts"><value>0.00</value></property>
      <property name="weight"><value>0.0200</value></property>
      <property name="stock"><value>2</value></property>
      <property name="brand"><value>Audio Dynavox</value></property>
      <property name="type"><value>kabels/stekkers/converters</value></property>
    </properties>
    <variations/>
  </product>
</products>
XML);

        $feed = Feed::query()->create([
            'site_id' => $site->id,
            'partner_id' => $partner->id,
            'name' => 'TradeTracker XML',
            'slug' => 'tradetracker-xml',
            'provider' => 'tradetracker',
            'source_type' => 'file',
            'source_format' => 'xml',
            'source_file_path' => $path,
            'row_selector' => 'product',
            'decimal_separator' => '.',
            'unique_identifier_field' => 'external_id',
            'import_create_new' => true,
            'import_update_existing' => true,
            'is_active' => true,
        ]);

        $this->mapField($feed, 'external_id', '@attributes.ID');
        $this->mapField($feed, 'network_campaign_id', 'campaignID');
        $this->mapField($feed, 'title', 'name');
        $this->mapField($feed, 'description', 'description');
        $this->mapField($feed, 'merchant_category', 'properties.property[name=categoryPath].value', 'first_value');
        $this->mapField($feed, 'product_type', 'properties.property[name=type].value');
        $this->mapField($feed, 'affiliate_url', 'URL');
        $this->mapField($feed, 'tracking_url', 'URL');
        $this->mapField($feed, 'image_url', 'images.image', 'first_value');
        $this->mapField($feed, 'additional_image_urls', 'images.image', 'array');
        $this->mapField($feed, 'price', 'price', 'money');
        $this->mapField($feed, 'old_price', 'properties.property[name=fromPrice].value', 'money');
        $this->mapField($feed, 'shipping_cost', 'properties.property[name=deliveryCosts].value', 'money');
        $this->mapField($feed, 'availability', 'properties.property[name=stock].value', 'stock_availability');
        $this->mapField($feed, 'stock_quantity', 'properties.property[name=stock].value', 'integer');
        $this->mapField($feed, 'delivery_time', 'properties.property[name=deliveryTime].value');
        $this->mapField($feed, 'brand', 'properties.property[name=brand].value');
        $this->mapField($feed, 'gtin', 'properties.property[name=EAN].value');
        $this->mapField($feed, 'mpn', 'properties.property[name=MPN].value');
        $this->mapField($feed, 'weight', 'properties.property[name=weight].value');

        $batch = app(FeedImporter::class)->import($feed);

        $this->assertSame('completed', $batch->status);
        $this->assertSame(1, $batch->created_rows);

        $product = Product::query()->firstOrFail();

        $this->assertSame('12b7e85410114ba3b61cc02ee2ed5c19dcb22ac6', $product->provider_product_id);
        $this->assertSame('Audio Dynavox Black Line Cinchkabel Stereo 1,5 meter', $product->title);
        $this->assertSame('Audio Dynavox', $product->brand);
        $this->assertSame('4250019131523', $product->ean);
        $this->assertSame('3586', $product->mpn);
        $this->assertSame('80.25', $product->price);
        $this->assertSame('107.00', $product->old_price);
        $this->assertSame('0.00', $product->shipping_cost);
        $this->assertSame('in_stock', $product->availability);
        $this->assertSame(2, $product->stock_quantity);
        $this->assertSame('Accessoires', $product->merchant_category);
        $this->assertSame('kabels/stekkers/converters', $product->product_type);
        $this->assertSame('https://www.audioshop.nl/image-1.jpg', $product->image_url);
        $this->assertSame([
            'https://www.audioshop.nl/image-1.jpg',
            'https://www.audioshop.nl/image-2.jpg',
        ], $product->additional_image_urls);
        $this->assertSame('20790', $product->metadata['network']['campaign_id']);
        $this->assertSame('0.0200', $product->metadata['specifications']['weight']);
    }

    private function mapField(Feed $feed, string $canonicalKey, string $sourceField, string $transform = 'copy'): void
    {
        $field = CanonicalField::query()->where('key', $canonicalKey)->firstOrFail();

        $feed->productFieldMappings()->create([
            'canonical_field_id' => $field->id,
            'mapping_action' => 'map',
            'source_field' => $sourceField,
            'source_path' => $sourceField,
            'transform_type' => $transform,
            'is_required' => $field->is_required,
            'sort_order' => $field->sort_order,
        ]);
    }
}
