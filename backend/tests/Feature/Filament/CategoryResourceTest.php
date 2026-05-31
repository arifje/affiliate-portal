<?php

namespace Tests\Feature\Filament;

use App\Models\Category;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_can_be_managed_from_the_admin_panel(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $site = Site::query()->create([
            'name' => 'Hartslagmeters',
            'slug' => 'hartslagmeters_nl',
            'primary_domain' => 'hartslagmeters.nl',
        ]);

        Category::query()->create([
            'site_id' => $site->id,
            'name' => 'Polsmeters',
            'slug' => 'polsmeters',
            'hero_image' => 'sites/hartslagmeters-nl/categories/polsmeters/hero/header.jpg',
            'is_active' => true,
        ]);

        $this
            ->actingAs($user)
            ->get('/admin/categories')
            ->assertOk()
            ->assertSee('Polsmeters');

        $this
            ->actingAs($user)
            ->get('/admin/categories/create')
            ->assertOk()
            ->assertSee('Hero image');
    }
}
