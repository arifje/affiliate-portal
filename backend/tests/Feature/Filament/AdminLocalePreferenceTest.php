<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLocalePreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_locale_is_applied_from_the_authenticated_user(): void
    {
        $user = User::factory()->create([
            'admin_locale' => 'nl',
            'is_active' => true,
        ]);

        $this
            ->actingAs($user)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('Overzicht')
            ->assertSee('Feed-imports')
            ->assertSee('Catalogus')
            ->assertSee('Producten')
            ->assertSee('Gebruikers')
            ->assertSee('E-mailadres')
            ->assertSee('Beheertaal')
            ->assertSee('Instellingen')
            ->assertSee('Systeem');

        $this->assertSame('nl', app()->getLocale());
    }

    public function test_admin_locale_falls_back_to_the_application_locale(): void
    {
        $user = User::factory()->make([
            'admin_locale' => 'invalid',
        ]);

        $this->assertSame(config('app.locale'), $user->preferredLocale());
    }
}
