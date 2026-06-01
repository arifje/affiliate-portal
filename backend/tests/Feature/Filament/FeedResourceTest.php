<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Feeds\FeedResource;
use App\Filament\Resources\Feeds\Pages\ListFeeds;
use App\Filament\Resources\Feeds\Pages\ViewFeed;
use App\Models\Feed;
use App\Models\Partner;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FeedResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_can_be_deleted_from_the_table_without_livewire_modal_errors(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $feed = $this->createFeed();

        Livewire::actingAs($user)
            ->test(ListFeeds::class)
            ->callTableAction('delete', $feed)
            ->assertRedirect(FeedResource::getUrl());

        $this->assertModelMissing($feed);
    }

    public function test_feed_can_be_deleted_from_the_view_page_without_livewire_modal_errors(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $feed = $this->createFeed();

        Livewire::actingAs($user)
            ->test(ViewFeed::class, ['record' => $feed->getRouteKey()])
            ->callAction('delete')
            ->assertRedirect(FeedResource::getUrl());

        $this->assertModelMissing($feed);
    }

    private function createFeed(): Feed
    {
        $site = Site::query()->create([
            'name' => 'Hartslagmeters',
            'slug' => 'hartslagmeters_nl',
            'primary_domain' => 'hartslagmeters.test',
        ]);

        $partner = Partner::query()->create([
            'name' => 'Demo Partner',
            'slug' => 'demo-partner',
            'provider' => 'awin',
            'is_active' => true,
        ]);

        return Feed::query()->create([
            'site_id' => $site->id,
            'partner_id' => $partner->id,
            'name' => 'Demo Feed',
            'slug' => 'demo-feed',
            'provider' => 'awin',
            'source_type' => 'file',
            'source_format' => 'csv',
            'is_active' => true,
        ]);
    }
}
