<?php

namespace Database\Seeders\Demo;

use App\Models\Category;
use App\Models\CanonicalField;
use App\Models\Feed;
use App\Models\Partner;
use App\Models\Product;
use App\Models\Site;
use Database\Seeders\FeedMapping\CanonicalFieldSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DemoHartslagmetersSeeder extends Seeder
{
    /**
     * Seed demo catalog data for the first heart-rate-monitor site.
     */
    public function run(): void
    {
        $this->call(CanonicalFieldSeeder::class);

        $site = Site::query()->firstOrCreate(
            ['slug' => 'hartslagmeters_nl'],
            [
                'name' => 'hartslagmeters.nl',
                'primary_domain' => 'hartslagmeters.nl',
                'domain_aliases' => ['www.hartslagmeters.nl'],
                'locale' => 'nl_NL',
                'currency' => 'EUR',
                'timezone' => 'Europe/Amsterdam',
                'theme' => [
                    'primary_color' => '#0f766e',
                    'accent_color' => '#d97706',
                    'surface_color' => '#ffffff',
                    'font_family' => 'Inter, ui-sans-serif, system-ui, sans-serif',
                ],
                'layout' => [
                    'home_template' => 'product_grid',
                ],
                'is_active' => true,
            ]
        );

        $partner = Partner::query()->updateOrCreate(
            ['slug' => 'demo-awin-sportshop'],
            [
                'name' => 'Demo Awin Sportshop',
                'provider' => 'awin',
                'website_url' => 'https://example.com',
                'settings' => [
                    'demo' => true,
                    'note' => 'Local Awin-style demo merchant for feed import and storefront testing.',
                ],
                'is_active' => true,
            ]
        );

        $sourceFilePath = $this->writeDemoFeedFile($site);
        $analysis = $this->demoFeedAnalysis();

        $feed = Feed::query()->updateOrCreate(
            [
                'site_id' => $site->id,
                'slug' => 'hartslagmeters-demo-awin-csv',
            ],
            [
                'partner_id' => $partner->id,
                'name' => 'Hartslagmeters Demo Awin CSV',
                'provider' => 'awin',
                'source_type' => 'file',
                'source_format' => 'csv',
                'source_encoding' => 'utf-8',
                'source_file_path' => $sourceFilePath,
                'source_file_original_name' => ['demo-hartslagmeters-awin.csv'],
                'source_url' => null,
                'delimiter' => ';',
                'enclosure' => '"',
                'decimal_separator' => ',',
                'thousands_separator' => '.',
                'row_selector' => 'rows',
                'first_row_is_header' => true,
                'available_elements' => $analysis['available_elements'],
                'sample_fields' => $analysis['sample_fields'],
                'sample_payload' => $analysis['sample_payload'],
                'last_analyzed_at' => now(),
                'unique_identifier_field' => 'external_id',
                'import_create_new' => true,
                'import_update_existing' => true,
                'import_disable_missing_globally' => false,
                'import_disable_missing_for_site' => false,
                'import_delete_missing' => false,
                'import_update_search_indexes' => true,
                'schedule' => 'manual',
                'last_import_status' => 'demo_seeded',
                'last_import_message' => 'Demo products seeded locally.',
                'last_import_started_at' => now(),
                'last_import_finished_at' => now(),
                'is_active' => true,
            ]
        );

        $this->seedProductFieldMappings($feed);

        $categories = $this->seedCategories($site);

        foreach ($this->products() as $product) {
            Product::query()->updateOrCreate(
                [
                    'site_id' => $site->id,
                    'partner_id' => $partner->id,
                    'provider_product_id' => $product['provider_product_id'],
                ],
                [
                    'feed_id' => $feed->id,
                    'category_id' => $categories[$product['category_slug']]->id,
                    'sku' => $product['sku'],
                    'ean' => $product['ean'],
                    'mpn' => $product['mpn'],
                    'brand' => $product['brand'],
                    'title' => $product['title'],
                    'slug' => $product['slug'],
                    'description' => $product['description'],
                    'image_url' => $this->placeholderUrl($product['title'], $product['image_bg'], $product['image_fg']),
                    'additional_image_urls' => [
                        $this->placeholderUrl($product['brand'], 'ffffff', $product['image_fg']),
                    ],
                    'product_url' => 'https://example.com/products/'.$product['slug'],
                    'affiliate_url' => 'https://example.com/go/'.$product['slug'],
                    'tracking_url' => 'https://example.com/track/'.$product['slug'].'?utm_source=hartslagmeters_nl',
                    'price' => $product['price'],
                    'old_price' => $product['old_price'],
                    'currency' => 'EUR',
                    'availability' => $product['availability'],
                    'condition' => 'new',
                    'merchant_category' => $product['merchant_category'],
                    'product_type' => $product['product_type'],
                    'shipping_cost' => $product['shipping_cost'],
                    'stock_quantity' => $product['stock_quantity'],
                    'delivery_time' => $product['delivery_time'],
                    'color' => $product['color'],
                    'size' => $product['size'],
                    'gender' => 'unisex',
                    'material' => $product['material'],
                    'age_group' => 'adult',
                    'metadata' => [
                        'demo' => true,
                        'highlights' => $product['highlights'],
                        'battery_life' => $product['battery_life'],
                        'connectivity' => $product['connectivity'],
                    ],
                    'raw_payload' => [
                        'aw_product_id' => $product['provider_product_id'],
                        'merchant_product_id' => $product['sku'],
                        'product_name' => $product['title'],
                        'search_price' => number_format((float) $product['price'], 2, ',', ''),
                    ],
                    'imported_at' => now(),
                    'published_at' => now(),
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * @return array<string, Category>
     */
    private function seedCategories(Site $site): array
    {
        $categories = [
            'polsmeters' => [
                'name' => 'Polsmeters',
                'description' => 'Optische hartslagmeters voor hardlopen, fitness en dagelijks gebruik.',
                'sort_order' => 10,
            ],
            'borstbanden' => [
                'name' => 'Borstbanden',
                'description' => 'Nauwkeurige borstbanden voor sporters die stabiele metingen willen.',
                'sort_order' => 20,
            ],
            'sporthorloges' => [
                'name' => 'Sporthorloges',
                'description' => 'Sporthorloges met hartslag, GPS en trainingsinzichten.',
                'sort_order' => 30,
            ],
            'fietscomputers' => [
                'name' => 'Fietscomputers',
                'description' => 'Fietsgerichte bundles met hartslag, cadans en GPS-koppeling.',
                'sort_order' => 40,
            ],
            'accessoires' => [
                'name' => 'Accessoires',
                'description' => 'Bandjes, dongles en onderdelen voor hartslagmeters.',
                'sort_order' => 50,
            ],
        ];

        return collect($categories)
            ->mapWithKeys(fn (array $category, string $slug): array => [
                $slug => Category::query()->updateOrCreate(
                    [
                        'site_id' => $site->id,
                        'slug' => $slug,
                    ],
                    [
                        'name' => $category['name'],
                        'description' => $category['description'],
                        'sort_order' => $category['sort_order'],
                        'meta_title' => $category['name'].' kopen',
                        'meta_description' => $category['description'],
                        'is_active' => true,
                    ]
                ),
            ])
            ->all();
    }

    private function writeDemoFeedFile(Site $site): string
    {
        $path = "feeds/site-{$site->id}/demo-hartslagmeters-awin.csv";
        $handle = fopen('php://temp', 'r+');

        $headers = [
            'aw_product_id',
            'merchant_product_id',
            'product_name',
            'description',
            'merchant_product_category_path',
            'merchant_category',
            'aw_deep_link',
            'merchant_deep_link',
            'merchant_image_url',
            'search_price',
            'rrp_price',
            'currency',
            'delivery_cost',
            'in_stock',
            'stock_quantity',
            'condition',
            'brand_name',
            'product_GTIN',
            'mpn',
            'colour',
            'product_type',
            'delivery_time',
        ];

        fputcsv($handle, $headers, ';', '"', '');

        foreach ($this->products() as $product) {
            fputcsv($handle, [
                $product['provider_product_id'],
                $product['sku'],
                $product['title'],
                $product['description'],
                $product['merchant_category'],
                $product['merchant_category'],
                'https://www.awin1.com/cread.php?awinmid=demo&ued='.rawurlencode('https://example.com/go/'.$product['slug']),
                'https://example.com/products/'.$product['slug'],
                $this->placeholderUrl($product['title'], $product['image_bg'], $product['image_fg']),
                number_format((float) $product['price'], 2, ',', ''),
                $product['old_price'] !== null ? number_format((float) $product['old_price'], 2, ',', '') : '',
                'EUR',
                number_format((float) $product['shipping_cost'], 2, ',', ''),
                $product['availability'] === 'in_stock' ? 'yes' : 'no',
                $product['stock_quantity'],
                'new',
                $product['brand'],
                $product['ean'],
                $product['mpn'],
                $product['color'],
                $product['product_type'],
                $product['delivery_time'],
            ], ';', '"', '');
        }

        rewind($handle);
        Storage::disk('local')->put($path, stream_get_contents($handle));
        fclose($handle);

        return $path;
    }

    /**
     * @return array<string, mixed>
     */
    private function demoFeedAnalysis(): array
    {
        $sample = $this->products()[0];
        $samplePayload = [
            'aw_product_id' => $sample['provider_product_id'],
            'merchant_product_id' => $sample['sku'],
            'product_name' => $sample['title'],
            'description' => $sample['description'],
            'merchant_product_category_path' => $sample['merchant_category'],
            'merchant_category' => $sample['merchant_category'],
            'aw_deep_link' => 'https://www.awin1.com/cread.php?awinmid=demo',
            'merchant_deep_link' => 'https://example.com/products/'.$sample['slug'],
            'merchant_image_url' => $this->placeholderUrl($sample['title'], $sample['image_bg'], $sample['image_fg']),
            'search_price' => number_format((float) $sample['price'], 2, ',', ''),
            'rrp_price' => number_format((float) $sample['old_price'], 2, ',', ''),
            'currency' => 'EUR',
            'delivery_cost' => number_format((float) $sample['shipping_cost'], 2, ',', ''),
            'in_stock' => 'yes',
            'stock_quantity' => $sample['stock_quantity'],
            'condition' => 'new',
            'brand_name' => $sample['brand'],
            'product_GTIN' => $sample['ean'],
            'mpn' => $sample['mpn'],
            'colour' => $sample['color'],
            'product_type' => $sample['product_type'],
            'delivery_time' => $sample['delivery_time'],
        ];

        return [
            'available_elements' => [
                ['path' => 'rows', 'label' => 'rows', 'count' => count($this->products())],
            ],
            'sample_fields' => collect($samplePayload)
                ->map(fn (mixed $sample, string $path): array => [
                    'path' => $path,
                    'label' => $path,
                    'sample' => $sample,
                ])
                ->values()
                ->all(),
            'sample_payload' => $samplePayload,
        ];
    }

    private function seedProductFieldMappings(Feed $feed): void
    {
        $mappings = [
            'external_id' => ['source_field' => 'aw_product_id'],
            'sku' => ['source_field' => 'merchant_product_id'],
            'title' => ['source_field' => 'product_name'],
            'description' => ['source_field' => 'description'],
            'category_path' => ['source_field' => 'merchant_product_category_path'],
            'merchant_category' => ['source_field' => 'merchant_category'],
            'merchant_url' => ['source_field' => 'merchant_deep_link'],
            'affiliate_url' => ['source_field' => 'aw_deep_link'],
            'image_url' => ['source_field' => 'merchant_image_url'],
            'price' => ['source_field' => 'search_price', 'transform_type' => 'money'],
            'old_price' => ['source_field' => 'rrp_price', 'transform_type' => 'money'],
            'currency' => ['source_field' => 'currency'],
            'shipping_cost' => ['source_field' => 'delivery_cost', 'transform_type' => 'money'],
            'availability' => ['source_field' => 'in_stock', 'transform_type' => 'availability'],
            'stock_quantity' => ['source_field' => 'stock_quantity', 'transform_type' => 'integer'],
            'condition' => ['source_field' => 'condition'],
            'brand' => ['source_field' => 'brand_name'],
            'gtin' => ['source_field' => 'product_GTIN'],
            'mpn' => ['source_field' => 'mpn'],
            'color' => ['source_field' => 'colour'],
            'product_type' => ['source_field' => 'product_type'],
            'delivery_time' => ['source_field' => 'delivery_time'],
        ];
        $sampleFields = collect($feed->sample_fields ?? [])->keyBy('path');

        foreach ($mappings as $canonicalKey => $mapping) {
            $field = CanonicalField::query()->where('key', $canonicalKey)->first();

            if (! $field) {
                continue;
            }

            $sourceField = $mapping['source_field'];

            $feed->productFieldMappings()->updateOrCreate(
                ['canonical_field_id' => $field->id],
                [
                    'mapping_action' => 'map',
                    'source_field' => $sourceField,
                    'source_path' => $sourceField,
                    'source_sample' => $sampleFields->get($sourceField)['sample'] ?? null,
                    'fallback_fields' => $mapping['fallback_fields'] ?? [],
                    'default_value' => $mapping['default_value'] ?? null,
                    'transform_type' => $mapping['transform_type'] ?? $this->defaultTransformForField($field),
                    'transform_config' => $mapping['transform_config'] ?? null,
                    'is_required' => $field->is_required,
                    'sort_order' => $field->sort_order,
                ]
            );
        }
    }

    private function defaultTransformForField(CanonicalField $field): string
    {
        return match ($field->data_type) {
            'array' => 'array',
            'boolean' => 'boolean',
            'decimal' => 'decimal',
            'integer' => 'integer',
            'url' => 'url',
            default => 'copy',
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function products(): array
    {
        return [
            [
                'provider_product_id' => 'DEMO-HRM-001',
                'sku' => 'PP-PRO5-GRAPHITE',
                'ean' => '8720000000011',
                'mpn' => 'PP-PRO5',
                'brand' => 'PulsePeak',
                'title' => 'PulsePeak Pro 5 Optische Hartslagmeter',
                'slug' => 'demo-pulsepeak-pro-5-optische-hartslagmeter',
                'category_slug' => 'polsmeters',
                'description' => 'Comfortabele optische hartslagmeter met trainingszones, HRV-meting en een helder scherm voor dagelijkse workouts.',
                'price' => 89.95,
                'old_price' => 119.95,
                'availability' => 'in_stock',
                'shipping_cost' => 0,
                'stock_quantity' => 24,
                'delivery_time' => '1-2 werkdagen',
                'merchant_category' => 'Hartslagmeters > Polsmeters',
                'product_type' => 'Wearables > Hartslagmeters',
                'color' => 'Graphite',
                'size' => 'M/L',
                'material' => 'Silicone',
                'battery_life' => '7 dagen',
                'connectivity' => ['Bluetooth', 'ANT+'],
                'highlights' => ['Optische sensor', 'HRV-meting', 'Waterbestendig'],
                'image_bg' => 'e8f5f1',
                'image_fg' => '0f766e',
            ],
            [
                'provider_product_id' => 'DEMO-HRM-002',
                'sku' => 'CF-ARMBAND-HRM',
                'ean' => '8720000000028',
                'mpn' => 'CF-AB1',
                'brand' => 'CardioFit',
                'title' => 'CardioFit ArmBand HRM',
                'slug' => 'demo-cardiofit-armband-hrm',
                'category_slug' => 'polsmeters',
                'description' => 'Lichtgewicht armhartslagmeter voor intervaltraining en groepslessen, inclusief verstelbare band.',
                'price' => 54.95,
                'old_price' => 69.95,
                'availability' => 'in_stock',
                'shipping_cost' => 3.95,
                'stock_quantity' => 38,
                'delivery_time' => 'Morgen in huis',
                'merchant_category' => 'Hartslagmeters > Polsmeters',
                'product_type' => 'Wearables > Hartslagmeters',
                'color' => 'Black',
                'size' => 'One size',
                'material' => 'Nylon',
                'battery_life' => '20 uur',
                'connectivity' => ['Bluetooth'],
                'highlights' => ['Verstelbare armband', 'Snellaadfunctie', 'Compact'],
                'image_bg' => 'f2f4f7',
                'image_fg' => '1f2937',
            ],
            [
                'provider_product_id' => 'DEMO-HRM-003',
                'sku' => 'VB-CHEST-DUO',
                'ean' => '8720000000035',
                'mpn' => 'VB-DUO',
                'brand' => 'VeloBeat',
                'title' => 'VeloBeat Chest Strap Duo',
                'slug' => 'demo-velobeat-chest-strap-duo',
                'category_slug' => 'borstbanden',
                'description' => 'Nauwkeurige borstband met dual-band koppeling voor fietscomputers, sporthorloges en fitnessapparatuur.',
                'price' => 39.95,
                'old_price' => null,
                'availability' => 'in_stock',
                'shipping_cost' => 2.95,
                'stock_quantity' => 61,
                'delivery_time' => '1-2 werkdagen',
                'merchant_category' => 'Hartslagmeters > Borstbanden',
                'product_type' => 'Wearables > Borstbanden',
                'color' => 'Black',
                'size' => 'XS-XL',
                'material' => 'Textiel',
                'battery_life' => '400 uur',
                'connectivity' => ['Bluetooth', 'ANT+'],
                'highlights' => ['Dual-band', 'Vervangbare batterij', 'Wasbare band'],
                'image_bg' => 'eef2ff',
                'image_fg' => '3730a3',
            ],
            [
                'provider_product_id' => 'DEMO-HRM-004',
                'sku' => 'TT-HR-BELT',
                'ean' => '8720000000042',
                'mpn' => 'TT-HRB',
                'brand' => 'TempoTrack',
                'title' => 'TempoTrack HR Chest Belt',
                'slug' => 'demo-tempotrack-hr-chest-belt',
                'category_slug' => 'borstbanden',
                'description' => 'Robuuste borstband voor duurtraining met stabiele meting bij hoge intensiteit.',
                'price' => 49.00,
                'old_price' => 59.00,
                'availability' => 'in_stock',
                'shipping_cost' => 0,
                'stock_quantity' => 17,
                'delivery_time' => '2-3 werkdagen',
                'merchant_category' => 'Hartslagmeters > Borstbanden',
                'product_type' => 'Wearables > Borstbanden',
                'color' => 'Blue',
                'size' => 'S-L',
                'material' => 'Elastaan',
                'battery_life' => '300 uur',
                'connectivity' => ['ANT+'],
                'highlights' => ['Stabiel signaal', 'Zachte band', 'Training ready'],
                'image_bg' => 'eff6ff',
                'image_fg' => '1d4ed8',
            ],
            [
                'provider_product_id' => 'DEMO-HRM-005',
                'sku' => 'HP-SW42',
                'ean' => '8720000000059',
                'mpn' => 'HP-42',
                'brand' => 'HeartPilot',
                'title' => 'HeartPilot Sportwatch 42mm',
                'slug' => 'demo-heartpilot-sportwatch-42mm',
                'category_slug' => 'sporthorloges',
                'description' => 'Sporthorloge met ingebouwde hartslagmeting, GPS-routes en hersteladvies na je training.',
                'price' => 149.95,
                'old_price' => 179.95,
                'availability' => 'in_stock',
                'shipping_cost' => 0,
                'stock_quantity' => 12,
                'delivery_time' => 'Morgen in huis',
                'merchant_category' => 'Sporthorloges > GPS horloges',
                'product_type' => 'Wearables > Sporthorloges',
                'color' => 'Forest Green',
                'size' => '42 mm',
                'material' => 'Aluminium',
                'battery_life' => '10 dagen',
                'connectivity' => ['Bluetooth', 'GPS'],
                'highlights' => ['GPS', 'Hersteladvies', 'Slaaptracking'],
                'image_bg' => 'ecfdf5',
                'image_fg' => '047857',
            ],
            [
                'provider_product_id' => 'DEMO-HRM-006',
                'sku' => 'MB-RUNNER-WATCH',
                'ean' => '8720000000066',
                'mpn' => 'MB-RW',
                'brand' => 'MotionBeat',
                'title' => 'MotionBeat Runner Watch',
                'slug' => 'demo-motionbeat-runner-watch',
                'category_slug' => 'sporthorloges',
                'description' => 'Hardloophorloge met hartslagzones, tempo-alerts en trainingsbelasting voor serieuze schemas.',
                'price' => 199.00,
                'old_price' => null,
                'availability' => 'preorder',
                'shipping_cost' => 0,
                'stock_quantity' => 0,
                'delivery_time' => 'Leverbaar vanaf volgende week',
                'merchant_category' => 'Sporthorloges > Hardloophorloges',
                'product_type' => 'Wearables > Sporthorloges',
                'color' => 'White',
                'size' => '45 mm',
                'material' => 'Polymeer',
                'battery_life' => '14 dagen',
                'connectivity' => ['Bluetooth', 'GPS', 'Wi-Fi'],
                'highlights' => ['Tempo-alerts', 'Trainingsbelasting', 'Lange batterijduur'],
                'image_bg' => 'fefce8',
                'image_fg' => 'a16207',
            ],
            [
                'provider_product_id' => 'DEMO-HRM-007',
                'sku' => 'CP-GPS-BUNDLE',
                'ean' => '8720000000073',
                'mpn' => 'CP-GPS-HR',
                'brand' => 'CyclePulse',
                'title' => 'CyclePulse GPS Hartslag Bundle',
                'slug' => 'demo-cyclepulse-gps-hartslag-bundle',
                'category_slug' => 'fietscomputers',
                'description' => 'Complete fietscomputer-bundel met GPS, hartslagband en stuurhouder voor rittenanalyse.',
                'price' => 129.95,
                'old_price' => 159.95,
                'availability' => 'in_stock',
                'shipping_cost' => 0,
                'stock_quantity' => 15,
                'delivery_time' => '1-2 werkdagen',
                'merchant_category' => 'Fietsen > Fietscomputers',
                'product_type' => 'Sportelektronica > Fietscomputers',
                'color' => 'Black',
                'size' => 'Bundle',
                'material' => 'Kunststof',
                'battery_life' => '18 uur',
                'connectivity' => ['Bluetooth', 'ANT+', 'GPS'],
                'highlights' => ['Inclusief hartslagband', 'GPS routes', 'Stuurhouder'],
                'image_bg' => 'f1f5f9',
                'image_fg' => '334155',
            ],
            [
                'provider_product_id' => 'DEMO-HRM-008',
                'sku' => 'AB-BIKE-SENSOR',
                'ean' => '8720000000080',
                'mpn' => 'AB-SENSOR-SET',
                'brand' => 'AeroBeat',
                'title' => 'AeroBeat Bike Sensor Set',
                'slug' => 'demo-aerobeat-bike-sensor-set',
                'category_slug' => 'fietscomputers',
                'description' => 'Sensorenset voor cadans, snelheid en hartslagkoppeling tijdens indoor en outdoor fietstraining.',
                'price' => 74.50,
                'old_price' => 89.95,
                'availability' => 'in_stock',
                'shipping_cost' => 3.95,
                'stock_quantity' => 21,
                'delivery_time' => '2-3 werkdagen',
                'merchant_category' => 'Fietsen > Sensoren',
                'product_type' => 'Sportelektronica > Sensoren',
                'color' => 'Black',
                'size' => 'Set',
                'material' => 'Kunststof',
                'battery_life' => '250 uur',
                'connectivity' => ['Bluetooth', 'ANT+'],
                'highlights' => ['Cadans', 'Snelheid', 'Compatibel met apps'],
                'image_bg' => 'fff7ed',
                'image_fg' => 'c2410c',
            ],
            [
                'provider_product_id' => 'DEMO-HRM-009',
                'sku' => 'RR-HRV-RING',
                'ean' => '8720000000097',
                'mpn' => 'RR-HRV',
                'brand' => 'RecoveryRate',
                'title' => 'RecoveryRate HRV Ring',
                'slug' => 'demo-recoveryrate-hrv-ring',
                'category_slug' => 'polsmeters',
                'description' => 'Discrete ring voor rusthartslag, HRV en hersteltrends tijdens slaap en dagelijkse routines.',
                'price' => 99.95,
                'old_price' => 129.95,
                'availability' => 'in_stock',
                'shipping_cost' => 0,
                'stock_quantity' => 9,
                'delivery_time' => '1-2 werkdagen',
                'merchant_category' => 'Hartslagmeters > Herstel en HRV',
                'product_type' => 'Wearables > Hersteltracking',
                'color' => 'Silver',
                'size' => '10',
                'material' => 'Titanium',
                'battery_life' => '5 dagen',
                'connectivity' => ['Bluetooth'],
                'highlights' => ['HRV trends', 'Slaaptracking', 'Compact'],
                'image_bg' => 'faf5ff',
                'image_fg' => '7e22ce',
            ],
            [
                'provider_product_id' => 'DEMO-HRM-010',
                'sku' => 'FL-ANT-DONGLE',
                'ean' => '8720000000103',
                'mpn' => 'FL-ANT',
                'brand' => 'FitLink',
                'title' => 'FitLink ANT+ USB Dongle',
                'slug' => 'demo-fitlink-ant-usb-dongle',
                'category_slug' => 'accessoires',
                'description' => 'USB-dongle om ANT+ hartslagmeters te koppelen aan laptop, trainingssoftware en indoor cycling apps.',
                'price' => 19.95,
                'old_price' => null,
                'availability' => 'in_stock',
                'shipping_cost' => 2.95,
                'stock_quantity' => 84,
                'delivery_time' => 'Morgen in huis',
                'merchant_category' => 'Accessoires > Connectiviteit',
                'product_type' => 'Accessoires > Dongles',
                'color' => 'Black',
                'size' => 'Mini',
                'material' => 'Kunststof',
                'battery_life' => 'USB gevoed',
                'connectivity' => ['ANT+', 'USB'],
                'highlights' => ['Plug-and-play', 'Indoor training', 'Compact'],
                'image_bg' => 'f8fafc',
                'image_fg' => '475569',
            ],
            [
                'provider_product_id' => 'DEMO-HRM-011',
                'sku' => 'SC-SOFT-BAND',
                'ean' => '8720000000110',
                'mpn' => 'SC-BAND',
                'brand' => 'StrapCare',
                'title' => 'StrapCare Soft Replacement Band',
                'slug' => 'demo-strapcare-soft-replacement-band',
                'category_slug' => 'accessoires',
                'description' => 'Zachte vervangende borstband voor dagelijks trainen, met verstelbare sluiting en sneldrogend materiaal.',
                'price' => 14.95,
                'old_price' => 19.95,
                'availability' => 'in_stock',
                'shipping_cost' => 2.95,
                'stock_quantity' => 46,
                'delivery_time' => '1-2 werkdagen',
                'merchant_category' => 'Accessoires > Bandjes',
                'product_type' => 'Accessoires > Vervangende banden',
                'color' => 'Grey',
                'size' => 'M-XL',
                'material' => 'Textiel',
                'battery_life' => 'Niet van toepassing',
                'connectivity' => [],
                'highlights' => ['Zacht materiaal', 'Sneldrogend', 'Verstelbaar'],
                'image_bg' => 'f3f4f6',
                'image_fg' => '4b5563',
            ],
            [
                'provider_product_id' => 'DEMO-HRM-012',
                'sku' => 'SP-WATER-HRM',
                'ean' => '8720000000127',
                'mpn' => 'SP-SWIM',
                'brand' => 'SwimPulse',
                'title' => 'SwimPulse Waterproof HRM',
                'slug' => 'demo-swimpulse-waterproof-hrm',
                'category_slug' => 'borstbanden',
                'description' => 'Waterbestendige hartslagmeter voor zwemtraining met geheugenfunctie en comfortabele siliconen grip.',
                'price' => 64.95,
                'old_price' => 79.95,
                'availability' => 'out_of_stock',
                'shipping_cost' => 0,
                'stock_quantity' => 0,
                'delivery_time' => 'Tijdelijk uitverkocht',
                'merchant_category' => 'Hartslagmeters > Zwemmen',
                'product_type' => 'Wearables > Zwemhartslagmeters',
                'color' => 'Navy',
                'size' => 'S-L',
                'material' => 'Silicone',
                'battery_life' => '200 uur',
                'connectivity' => ['Bluetooth'],
                'highlights' => ['Waterbestendig', 'Geheugenfunctie', 'Zwemtraining'],
                'image_bg' => 'ecfeff',
                'image_fg' => '0e7490',
            ],
        ];
    }

    private function placeholderUrl(string $text, string $background, string $foreground): string
    {
        return 'https://placehold.co/640x480/'.$background.'/'.$foreground.'/png?text='.str_replace('%20', '+', rawurlencode($text));
    }
}
