<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_system_page_is_available(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $this
            ->actingAs($user)
            ->get('/admin/system')
            ->assertOk()
            ->assertSee('Server info')
            ->assertSee('Load average')
            ->assertSee('Application info')
            ->assertSee('Requirements')
            ->assertSee('PHP version')
            ->assertSee('Database driver');
    }
}
