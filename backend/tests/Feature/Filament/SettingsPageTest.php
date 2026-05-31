<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\Settings;
use App\Models\AppSetting;
use App\Models\User;
use App\Support\PlatformSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_settings_page_is_available(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get('/admin/settings')
            ->assertOk()
            ->assertSee('Website status')
            ->assertSee('Website online')
            ->assertSee('Authentication')
            ->assertSee('Email connector');
    }

    public function test_admin_settings_can_be_saved(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $this->actingAs($user);

        Livewire::test(Settings::class)
            ->set('data.website_is_online', false)
            ->set('data.admin_login_method', PlatformSettings::LOGIN_METHOD_PASSWORD_OR_EMAIL_CODE)
            ->set('data.login_code_ttl_minutes', 15)
            ->set('data.login_code_length', 8)
            ->set('data.mail_driver', PlatformSettings::MAIL_DRIVER_SMTP)
            ->set('data.mail_from_name', 'Affiliate Portal')
            ->set('data.mail_from_email', 'noreply@example.com')
            ->set('data.smtp_host', 'smtp.example.com')
            ->set('data.smtp_port', 587)
            ->set('data.smtp_scheme', 'smtp')
            ->set('data.smtp_username', 'mailer')
            ->set('data.smtp_password', 'secret')
            ->call('save')
            ->assertHasNoErrors();

        $mailConnector = PlatformSettings::mailConnector();

        $this->assertFalse(PlatformSettings::websiteIsOnline());
        $this->assertSame(PlatformSettings::LOGIN_METHOD_PASSWORD_OR_EMAIL_CODE, PlatformSettings::adminLoginMethod());
        $this->assertSame(15, PlatformSettings::loginCodeTtlMinutes());
        $this->assertSame(8, PlatformSettings::loginCodeLength());
        $this->assertSame(PlatformSettings::MAIL_DRIVER_SMTP, $mailConnector['driver']);
        $this->assertSame('smtp.example.com', $mailConnector['smtp_host']);
        $this->assertSame('secret', $mailConnector['smtp_password']);
        $this->assertStringNotContainsString(
            'secret',
            (string) AppSetting::query()->where('key', PlatformSettings::MAIL_CONNECTOR)->value('value'),
        );
    }

    public function test_sendmail_connector_settings_can_be_saved(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $this->actingAs($user);

        Livewire::test(Settings::class)
            ->set('data.website_is_online', true)
            ->set('data.admin_login_method', PlatformSettings::LOGIN_METHOD_PASSWORD)
            ->set('data.login_code_ttl_minutes', 10)
            ->set('data.login_code_length', 6)
            ->set('data.mail_driver', PlatformSettings::MAIL_DRIVER_SENDMAIL)
            ->set('data.mail_from_name', 'Affiliate Portal')
            ->set('data.mail_from_email', 'noreply@example.com')
            ->set('data.sendmail_path', '/usr/sbin/sendmail -bs -i')
            ->call('save')
            ->assertHasNoErrors();

        $mailConnector = PlatformSettings::mailConnector();

        $this->assertSame(PlatformSettings::MAIL_DRIVER_SENDMAIL, $mailConnector['driver']);
        $this->assertSame('/usr/sbin/sendmail -bs -i', $mailConnector['sendmail_path']);
    }
}
