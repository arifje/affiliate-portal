<?php

namespace App\Support;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

class TransactionalMailer
{
    public function sendTestEmail(string $toEmail, ?string $toName = null): void
    {
        $appName = (string) config('app.name', 'Affiliate Portal');
        $subject = __('admin.auth.test_email.subject', ['app' => $appName]);
        $text = __('admin.auth.test_email.body', [
            'app' => $appName,
            'sent_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $this->send(
            toEmail: $toEmail,
            toName: $toName,
            subject: $subject,
            text: $text,
            html: nl2br(e($text)),
        );
    }

    public function sendLoginCode(User $user, string $code, CarbonInterface $expiresAt): void
    {
        $subject = __('admin.auth.login_code.subject');
        $text = __('admin.auth.login_code.body', [
            'code' => $code,
            'minutes' => max(1, (int) ceil(now()->diffInSeconds($expiresAt) / 60)),
        ]);
        $html = nl2br(e($text));

        $this->send(
            toEmail: $user->email,
            toName: $user->name,
            subject: $subject,
            text: $text,
            html: $html,
        );
    }

    public function send(string $toEmail, ?string $toName, string $subject, string $text, ?string $html = null): void
    {
        $settings = PlatformSettings::mailConnector();
        $driver = (string) ($settings['driver'] ?? PlatformSettings::MAIL_DRIVER_LOG);

        match ($driver) {
            PlatformSettings::MAIL_DRIVER_MAILERSEND_API => $this->sendViaMailerSend($settings, $toEmail, $toName, $subject, $text, $html),
            PlatformSettings::MAIL_DRIVER_SENDGRID_API => $this->sendViaSendGrid($settings, $toEmail, $toName, $subject, $text, $html),
            default => $this->sendViaLaravelMailer($settings, $toEmail, $toName, $subject, $text),
        };
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function sendViaLaravelMailer(array $settings, string $toEmail, ?string $toName, string $subject, string $text): void
    {
        $mailer = $this->configureLaravelMailer($settings);

        Mail::mailer($mailer)->raw($text, function (Message $message) use ($settings, $toEmail, $toName, $subject): void {
            $message
                ->from((string) $settings['from_email'], (string) $settings['from_name'])
                ->to($toEmail, $toName)
                ->subject($subject);
        });
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function sendViaMailerSend(array $settings, string $toEmail, ?string $toName, string $subject, string $text, ?string $html): void
    {
        $apiKey = $this->requireApiKey($settings);

        Http::withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post('https://api.mailersend.com/v1/email', [
                'from' => [
                    'email' => $settings['from_email'],
                    'name' => $settings['from_name'],
                ],
                'to' => [[
                    'email' => $toEmail,
                    'name' => $toName,
                ]],
                'subject' => $subject,
                'text' => $text,
                'html' => $html ?? nl2br(e($text)),
            ])
            ->throw();
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function sendViaSendGrid(array $settings, string $toEmail, ?string $toName, string $subject, string $text, ?string $html): void
    {
        $apiKey = $this->requireApiKey($settings);

        Http::withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->post('https://api.sendgrid.com/v3/mail/send', [
                'personalizations' => [[
                    'to' => [[
                        'email' => $toEmail,
                        'name' => $toName,
                    ]],
                ]],
                'from' => [
                    'email' => $settings['from_email'],
                    'name' => $settings['from_name'],
                ],
                'subject' => $subject,
                'content' => [
                    [
                        'type' => 'text/plain',
                        'value' => $text,
                    ],
                    [
                        'type' => 'text/html',
                        'value' => $html ?? nl2br(e($text)),
                    ],
                ],
            ])
            ->throw();
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function configureLaravelMailer(array $settings): string
    {
        $mailer = match ($settings['driver'] ?? PlatformSettings::MAIL_DRIVER_LOG) {
            PlatformSettings::MAIL_DRIVER_SMTP => 'admin_smtp',
            PlatformSettings::MAIL_DRIVER_SENDMAIL => 'admin_sendmail',
            default => 'admin_log',
        };

        config([
            'mail.from.address' => $settings['from_email'],
            'mail.from.name' => $settings['from_name'],
        ]);

        if ($mailer === 'admin_smtp') {
            config([
                'mail.mailers.admin_smtp' => [
                    'transport' => 'smtp',
                    'scheme' => filled($settings['smtp_scheme'] ?? null) ? $settings['smtp_scheme'] : null,
                    'url' => null,
                    'host' => $settings['smtp_host'],
                    'port' => $settings['smtp_port'],
                    'username' => $settings['smtp_username'] ?? null,
                    'password' => $settings['smtp_password'] ?? null,
                    'timeout' => null,
                    'local_domain' => parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST),
                ],
            ]);
        } elseif ($mailer === 'admin_sendmail') {
            config([
                'mail.mailers.admin_sendmail' => [
                    'transport' => 'sendmail',
                    'path' => $settings['sendmail_path'] ?? config('mail.mailers.sendmail.path'),
                ],
            ]);
        } else {
            config([
                'mail.mailers.admin_log' => [
                    'transport' => 'log',
                    'channel' => config('mail.mailers.log.channel'),
                ],
            ]);
        }

        app('mail.manager')->purge($mailer);

        return $mailer;
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function requireApiKey(array $settings): string
    {
        $apiKey = (string) ($settings['api_key'] ?? '');

        if (blank($apiKey)) {
            throw new InvalidArgumentException(__('admin.pages.settings.validation.mail_api_key_required'));
        }

        return $apiKey;
    }
}
