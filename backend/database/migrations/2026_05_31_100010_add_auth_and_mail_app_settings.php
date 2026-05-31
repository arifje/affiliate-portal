<?php

use App\Models\AppSetting;
use App\Support\PlatformSettings;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        AppSetting::query()->firstOrCreate(
            ['key' => PlatformSettings::ADMIN_LOGIN_METHOD],
            ['value' => json_encode(PlatformSettings::LOGIN_METHOD_PASSWORD)],
        );

        AppSetting::query()->firstOrCreate(
            ['key' => PlatformSettings::ADMIN_LOGIN_CODE_TTL_MINUTES],
            ['value' => json_encode(10)],
        );

        AppSetting::query()->firstOrCreate(
            ['key' => PlatformSettings::ADMIN_LOGIN_CODE_LENGTH],
            ['value' => json_encode(6)],
        );

        AppSetting::query()->firstOrCreate(
            ['key' => PlatformSettings::MAIL_CONNECTOR],
            ['value' => json_encode([
                'driver' => PlatformSettings::MAIL_DRIVER_LOG,
                'from_name' => config('mail.from.name'),
                'from_email' => config('mail.from.address'),
                'smtp_host' => config('mail.mailers.smtp.host'),
                'smtp_port' => config('mail.mailers.smtp.port'),
                'smtp_scheme' => config('mail.mailers.smtp.scheme'),
                'smtp_username' => config('mail.mailers.smtp.username'),
                'smtp_password' => config('mail.mailers.smtp.password'),
                'api_key' => null,
            ])],
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        AppSetting::query()
            ->whereIn('key', [
                PlatformSettings::ADMIN_LOGIN_METHOD,
                PlatformSettings::ADMIN_LOGIN_CODE_TTL_MINUTES,
                PlatformSettings::ADMIN_LOGIN_CODE_LENGTH,
                PlatformSettings::MAIL_CONNECTOR,
            ])
            ->delete();
    }
};
