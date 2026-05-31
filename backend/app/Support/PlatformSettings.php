<?php

namespace App\Support;

use App\Models\AppSetting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

class PlatformSettings
{
    public const WEBSITE_ONLINE = 'website_online';

    public const ADMIN_LOGIN_METHOD = 'admin_login_method';

    public const ADMIN_LOGIN_CODE_TTL_MINUTES = 'admin_login_code_ttl_minutes';

    public const ADMIN_LOGIN_CODE_LENGTH = 'admin_login_code_length';

    public const MAIL_CONNECTOR = 'mail_connector';

    public const LOGIN_METHOD_PASSWORD = 'password';

    public const LOGIN_METHOD_EMAIL_CODE = 'email_code';

    public const LOGIN_METHOD_PASSWORD_OR_EMAIL_CODE = 'password_or_email_code';

    public const MAIL_DRIVER_LOG = 'log';

    public const MAIL_DRIVER_SMTP = 'smtp';

    public const MAIL_DRIVER_SENDMAIL = 'sendmail';

    public const MAIL_DRIVER_MAILERSEND_API = 'mailersend_api';

    public const MAIL_DRIVER_SENDGRID_API = 'sendgrid_api';

    private const ENCRYPTED_SECRET_PREFIX = 'encrypted:';

    /**
     * @return array<string, string>
     */
    public static function adminLoginMethods(): array
    {
        return [
            self::LOGIN_METHOD_PASSWORD => __('admin.pages.settings.options.login_methods.password'),
            self::LOGIN_METHOD_EMAIL_CODE => __('admin.pages.settings.options.login_methods.email_code'),
            self::LOGIN_METHOD_PASSWORD_OR_EMAIL_CODE => __('admin.pages.settings.options.login_methods.password_or_email_code'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function mailDrivers(): array
    {
        return [
            self::MAIL_DRIVER_LOG => __('admin.pages.settings.options.mail_drivers.log'),
            self::MAIL_DRIVER_SMTP => __('admin.pages.settings.options.mail_drivers.smtp'),
            self::MAIL_DRIVER_SENDMAIL => __('admin.pages.settings.options.mail_drivers.sendmail'),
            self::MAIL_DRIVER_MAILERSEND_API => __('admin.pages.settings.options.mail_drivers.mailersend_api'),
            self::MAIL_DRIVER_SENDGRID_API => __('admin.pages.settings.options.mail_drivers.sendgrid_api'),
        ];
    }

    public static function websiteIsOnline(): bool
    {
        return (bool) AppSetting::getValue(self::WEBSITE_ONLINE, true);
    }

    public static function setWebsiteIsOnline(bool $isOnline): void
    {
        AppSetting::setValue(self::WEBSITE_ONLINE, $isOnline);
    }

    public static function adminLoginMethod(): string
    {
        $method = (string) AppSetting::getValue(self::ADMIN_LOGIN_METHOD, self::LOGIN_METHOD_PASSWORD);

        return array_key_exists($method, self::adminLoginMethods())
            ? $method
            : self::LOGIN_METHOD_PASSWORD;
    }

    public static function setAdminLoginMethod(string $method): void
    {
        AppSetting::setValue(
            self::ADMIN_LOGIN_METHOD,
            array_key_exists($method, self::adminLoginMethods()) ? $method : self::LOGIN_METHOD_PASSWORD,
        );
    }

    public static function loginCodeTtlMinutes(): int
    {
        return max(1, min(60, (int) AppSetting::getValue(self::ADMIN_LOGIN_CODE_TTL_MINUTES, 10)));
    }

    public static function setLoginCodeTtlMinutes(int $minutes): void
    {
        AppSetting::setValue(self::ADMIN_LOGIN_CODE_TTL_MINUTES, max(1, min(60, $minutes)));
    }

    public static function loginCodeLength(): int
    {
        return max(6, min(10, (int) AppSetting::getValue(self::ADMIN_LOGIN_CODE_LENGTH, 6)));
    }

    public static function setLoginCodeLength(int $length): void
    {
        AppSetting::setValue(self::ADMIN_LOGIN_CODE_LENGTH, max(6, min(10, $length)));
    }

    /**
     * @return array<string, mixed>
     */
    public static function mailConnector(): array
    {
        $settings = AppSetting::getValue(self::MAIL_CONNECTOR, []);

        if (! is_array($settings)) {
            $settings = [];
        }

        return [
            'driver' => $settings['driver'] ?? self::MAIL_DRIVER_LOG,
            'from_name' => $settings['from_name'] ?? config('mail.from.name'),
            'from_email' => $settings['from_email'] ?? config('mail.from.address'),
            'smtp_host' => $settings['smtp_host'] ?? config('mail.mailers.smtp.host'),
            'smtp_port' => (int) ($settings['smtp_port'] ?? config('mail.mailers.smtp.port', 2525)),
            'smtp_scheme' => $settings['smtp_scheme'] ?? config('mail.mailers.smtp.scheme'),
            'smtp_username' => $settings['smtp_username'] ?? config('mail.mailers.smtp.username'),
            'smtp_password' => array_key_exists('smtp_password', $settings)
                ? self::decryptSecret($settings['smtp_password'])
                : config('mail.mailers.smtp.password'),
            'sendmail_path' => $settings['sendmail_path'] ?? config('mail.mailers.sendmail.path'),
            'api_key' => self::decryptSecret($settings['api_key'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public static function setMailConnector(array $settings): void
    {
        $driver = (string) ($settings['driver'] ?? self::MAIL_DRIVER_LOG);

        AppSetting::setValue(self::MAIL_CONNECTOR, [
            'driver' => array_key_exists($driver, self::mailDrivers()) ? $driver : self::MAIL_DRIVER_LOG,
            'from_name' => $settings['from_name'] ?? null,
            'from_email' => $settings['from_email'] ?? null,
            'smtp_host' => $settings['smtp_host'] ?? null,
            'smtp_port' => filled($settings['smtp_port'] ?? null) ? (int) $settings['smtp_port'] : null,
            'smtp_scheme' => $settings['smtp_scheme'] ?? null,
            'smtp_username' => $settings['smtp_username'] ?? null,
            'smtp_password' => self::encryptSecret($settings['smtp_password'] ?? null),
            'sendmail_path' => $settings['sendmail_path'] ?? null,
            'api_key' => self::encryptSecret($settings['api_key'] ?? null),
        ]);
    }

    private static function encryptSecret(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $value = (string) $value;

        if (str_starts_with($value, self::ENCRYPTED_SECRET_PREFIX)) {
            return $value;
        }

        return self::ENCRYPTED_SECRET_PREFIX . Crypt::encryptString($value);
    }

    private static function decryptSecret(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $value = (string) $value;

        if (! str_starts_with($value, self::ENCRYPTED_SECRET_PREFIX)) {
            return $value;
        }

        try {
            return Crypt::decryptString(substr($value, strlen(self::ENCRYPTED_SECRET_PREFIX)));
        } catch (DecryptException) {
            return null;
        }
    }
}
