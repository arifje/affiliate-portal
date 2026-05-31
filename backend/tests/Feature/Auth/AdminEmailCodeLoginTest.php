<?php

namespace Tests\Feature\Auth;

use App\Filament\Auth\Login;
use App\Models\AdminLoginCode;
use App\Models\User;
use App\Support\PlatformSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminEmailCodeLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_with_email_code_when_enabled(): void
    {
        PlatformSettings::setAdminLoginMethod(PlatformSettings::LOGIN_METHOD_EMAIL_CODE);

        $user = User::factory()->create([
            'is_active' => true,
            'email' => 'admin@example.com',
        ]);

        AdminLoginCode::query()->create([
            'user_id' => $user->id,
            'email' => 'admin@example.com',
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
        ]);

        Livewire::test(Login::class)
            ->set('data.email', 'admin@example.com')
            ->set('data.code', '123456')
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
    }
}
