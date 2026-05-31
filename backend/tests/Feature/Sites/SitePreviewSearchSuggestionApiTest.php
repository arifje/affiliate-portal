<?php

namespace Tests\Feature\Sites;

use App\Models\Category;
use App\Models\Partner;
use App\Models\Product;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitePreviewSearchSuggestionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_site_scoped_search_suggestions(): void
    {
        $site = Site::query()->create([
            'name' => 'Hartslagmeters',
            'slug' => 'hartslagmeters_nl',
            'primary_domain' => 'hartslagmeters.nl',
        ]);

        $otherSite = Site::query()->create([
            'name' => 'Sweaters',
            'slug' => 'sweaters_nl',
            'primary_domain' => 'sweaters.nl',
        ]);

        $partner = Partner::query()->create([
            'name' => 'Demo Sportshop',
            'slug' => 'demo-sportshop',
            'provider' => 'custom',
        ]);

        $category = Category::query()->create([
            'site_id' => $site->id,
            'name' => 'Sporthorloges',
            'slug' => 'sporthorloges',
            'description' => 'Heart rate horloges voor sporters.',
            'is_active' => true,
        ]);

        Product::query()->create([
            'site_id' => $site->id,
            'partner_id' => $partner->id,
            'category_id' => $category->id,
            'brand' => 'HeartPilot',
            'title' => 'HeartPilot Sportwatch 42mm',
            'slug' => 'heartpilot-sportwatch-42mm',
            'affiliate_url' => 'https://example.com/heartpilot',
            'image_url' => 'https://img.example.com/watch.jpg',
            'price' => 149.95,
            'currency' => 'EUR',
            'is_active' => true,
        ]);

        Product::query()->create([
            'site_id' => $site->id,
            'partner_id' => $partner->id,
            'category_id' => $category->id,
            'brand' => 'HeartPilot',
            'title' => 'Inactive HeartPilot Watch',
            'slug' => 'inactive-heartpilot-watch',
            'affiliate_url' => 'https://example.com/inactive',
            'is_active' => false,
        ]);

        Product::query()->create([
            'site_id' => $otherSite->id,
            'partner_id' => $partner->id,
            'brand' => 'HeartPilot',
            'title' => 'Wrong Site HeartPilot Product',
            'slug' => 'wrong-site-heartpilot-product',
            'affiliate_url' => 'https://example.com/wrong-site',
            'is_active' => true,
        ]);

        $this->getJson('/api/sites/preview/hartslagmeters_nl/search-suggestions?q=heart')
            ->assertOk()
            ->assertJsonPath('query', 'heart')
            ->assertJsonFragment([
                'type' => 'product',
                'title' => 'HeartPilot Sportwatch 42mm',
                'slug' => 'heartpilot-sportwatch-42mm',
            ])
            ->assertJsonFragment([
                'type' => 'category',
                'title' => 'Sporthorloges',
                'slug' => 'sporthorloges',
            ])
            ->assertJsonFragment([
                'type' => 'brand',
                'title' => 'HeartPilot',
                'slug' => 'heartpilot',
            ])
            ->assertJsonMissing([
                'title' => 'Inactive HeartPilot Watch',
            ])
            ->assertJsonMissing([
                'title' => 'Wrong Site HeartPilot Product',
            ]);
    }

    public function test_it_returns_empty_suggestions_for_short_queries(): void
    {
        $site = Site::query()->create([
            'name' => 'Hartslagmeters',
            'slug' => 'hartslagmeters_nl',
            'primary_domain' => 'hartslagmeters.nl',
        ]);

        $this->getJson('/api/sites/preview/'.$site->slug.'/search-suggestions?q=h')
            ->assertOk()
            ->assertJsonPath('query', 'h')
            ->assertJsonCount(0, 'suggestions');
    }
}
