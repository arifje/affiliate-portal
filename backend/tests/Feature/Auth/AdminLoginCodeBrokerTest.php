<?php

namespace Tests\Feature\Auth;

use App\Models\AdminLoginCode;
use App\Models\User;
use App\Support\AdminLoginCodeBroker;
use App\Support\PlatformSettings;
use App\Support\TransactionalMailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminLoginCodeBrokerTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_code_can_be_consumed_once(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'email' => 'admin@example.com',
        ]);

        $loginCode = AdminLoginCode::query()->create([
            'user_id' => $user->id,
            'email' => 'admin@example.com',
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
        ]);

        $broker = app(AdminLoginCodeBroker::class);

        $this->assertTrue($user->is($broker->consume('admin@example.com', '123456')));
        $this->assertNotNull($loginCode->refresh()->consumed_at);
        $this->assertNull($broker->consume('admin@example.com', '123456'));
    }

    public function test_send_creates_hashed_code_and_queues_transactional_email(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'is_active' => true,
            'email' => 'admin@example.com',
        ]);

        PlatformSettings::setMailConnector([
            'driver' => PlatformSettings::MAIL_DRIVER_LOG,
            'from_name' => 'Affiliate Portal',
            'from_email' => 'noreply@example.com',
        ]);

        $sent = app(AdminLoginCodeBroker::class)->send(
            $user->email,
            Request::create('/admin/login', 'POST'),
        );

        $this->assertTrue($sent);
        $this->assertDatabaseHas('admin_login_codes', [
            'user_id' => $user->id,
            'email' => 'admin@example.com',
            'consumed_at' => null,
        ]);

        $this->assertNotSame(
            '123456',
            AdminLoginCode::query()->where('user_id', $user->id)->value('code_hash'),
        );

        Mail::assertNothingQueued();
    }

    public function test_mailersend_api_connector_sends_expected_payload(): void
    {
        Http::fake([
            'api.mailersend.com/*' => Http::response([], 202),
        ]);

        PlatformSettings::setMailConnector([
            'driver' => PlatformSettings::MAIL_DRIVER_MAILERSEND_API,
            'from_name' => 'Affiliate Portal',
            'from_email' => 'noreply@example.com',
            'api_key' => 'mailersend-token',
        ]);

        app(TransactionalMailer::class)->send(
            toEmail: 'admin@example.com',
            toName: 'Admin',
            subject: 'Login code',
            text: 'Your code is 123456',
            html: '<p>Your code is 123456</p>',
        );

        Http::assertSent(fn (HttpRequest $request): bool => $request->url() === 'https://api.mailersend.com/v1/email'
            && $request->hasHeader('Authorization', 'Bearer mailersend-token')
            && $request['from']['email'] === 'noreply@example.com'
            && $request['to'][0]['email'] === 'admin@example.com');
    }

    public function test_sendgrid_api_connector_sends_expected_payload(): void
    {
        Http::fake([
            'api.sendgrid.com/*' => Http::response([], 202),
        ]);

        PlatformSettings::setMailConnector([
            'driver' => PlatformSettings::MAIL_DRIVER_SENDGRID_API,
            'from_name' => 'Affiliate Portal',
            'from_email' => 'noreply@example.com',
            'api_key' => 'sendgrid-token',
        ]);

        app(TransactionalMailer::class)->send(
            toEmail: 'admin@example.com',
            toName: 'Admin',
            subject: 'Login code',
            text: 'Your code is 123456',
            html: '<p>Your code is 123456</p>',
        );

        Http::assertSent(fn (HttpRequest $request): bool => $request->url() === 'https://api.sendgrid.com/v3/mail/send'
            && $request->hasHeader('Authorization', 'Bearer sendgrid-token')
            && $request['from']['email'] === 'noreply@example.com'
            && $request['personalizations'][0]['to'][0]['email'] === 'admin@example.com');
    }

    public function test_sendmail_connector_can_send_through_laravel_mailer(): void
    {
        Mail::fake();

        PlatformSettings::setMailConnector([
            'driver' => PlatformSettings::MAIL_DRIVER_SENDMAIL,
            'from_name' => 'Affiliate Portal',
            'from_email' => 'noreply@example.com',
            'sendmail_path' => '/usr/sbin/sendmail -bs -i',
        ]);

        app(TransactionalMailer::class)->send(
            toEmail: 'admin@example.com',
            toName: 'Admin',
            subject: 'Login code',
            text: 'Your code is 123456',
        );

        Mail::assertNothingQueued();
    }
}
