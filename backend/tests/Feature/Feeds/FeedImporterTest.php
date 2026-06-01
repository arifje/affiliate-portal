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
