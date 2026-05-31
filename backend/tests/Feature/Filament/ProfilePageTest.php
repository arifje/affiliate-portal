<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ProfilePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_available_inside_admin_panel(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $this
            ->actingAs($user)
            ->get('/admin/profile')
            ->assertOk()
            ->assertSee('Profile')
            ->assertSee('Admin language');
    }

    public function test_profile_details_and_language_can_be_updated(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'admin_locale' => 'en',
        ]);

        $this->actingAs($user);

        Livewire::test(Profile::class)
            ->set('data.name', 'Arjan Brinkman')
            ->set('data.email', 'arjan@example.com')
            ->set('data.admin_locale', 'nl')
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertSame('Arjan Brinkman', $user->name);
        $this->assertSame('arjan@example.com', $user->email);
        $this->assertSame('nl', $user->admin_locale);
    }

    public function test_password_can_be_changed_with_current_password(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'password' => Hash::make('current-password'),
        ]);

        $this->actingAs($user);

        Livewire::test(Profile::class)
            ->set('data.current_password', 'current-password')
            ->set('data.password', 'new-secure-password')
            ->set('data.password_confirmation', 'new-secure-password')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('new-secure-password', $user->refresh()->password));
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'password' => Hash::make('current-password'),
        ]);

        $this->actingAs($user);

        Livewire::test(Profile::class)
            ->set('data.current_password', 'wrong-password')
            ->set('data.password', 'new-secure-password')
            ->set('data.password_confirmation', 'new-secure-password')
            ->call('save')
            ->assertHasErrors(['data.current_password']);

        $this->assertTrue(Hash::check('current-password', $user->refresh()->password));
    }
}
