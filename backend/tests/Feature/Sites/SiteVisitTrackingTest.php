<?php

namespace Tests\Feature\Sites;

use App\Models\Site;
use App\Models\SiteVisit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteVisitTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_tracks_unique_daily_site_visits_and_repeat_activity(): void
    {
        Site::query()->create([
            'name' => 'Hartslagmeters',
            'slug' => 'hartslagmeters_nl',
            'primary_domain' => 'hartslagmeters.nl',
        ]);

        $payload = [
            'visitor_id' => 'visitor-123',
            'path' => '/preview/hartslagmeters_nl',
            'referer' => 'https://example.com/source',
        ];

        $this->postJson('/api/sites/preview/hartslagmeters_nl/visits', $payload)
            ->assertCreated()
            ->assertJson([
                'tracked' => true,
                'unique' => true,
            ]);

        $this->postJson('/api/sites/preview/hartslagmeters_nl/visits', [
            ...$payload,
            'path' => '/preview/hartslagmeters_nl/products',
        ])
            ->assertOk()
            ->assertJson([
                'tracked' => true,
                'unique' => false,
            ]);

        $this->postJson('/api/sites/preview/hartslagmeters_nl/visits', [
            'visitor_id' => 'visitor-456',
            'path' => '/preview/hartslagmeters_nl/deals',
        ])->assertCreated();

        $this->assertSame(2, SiteVisit::query()->count());
        $this->assertSame(1, SiteVisit::query()->where('visit_count', 2)->count());
        $this->assertSame(1, SiteVisit::query()->where('visit_count', 1)->count());
        $this->assertDatabaseHas('site_visits', [
            'landing_path' => '/preview/hartslagmeters_nl',
            'last_path' => '/preview/hartslagmeters_nl/products',
            'referer' => 'https://example.com/source',
        ]);
    }
}
