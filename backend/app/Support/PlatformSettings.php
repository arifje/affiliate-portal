<?php

namespace App\Support;

use App\Models\AppSetting;

class PlatformSettings
{
    public const WEBSITE_ONLINE = 'website_online';

    public static function websiteIsOnline(): bool
    {
        return (bool) AppSetting::getValue(self::WEBSITE_ONLINE, true);
    }

    public static function setWebsiteIsOnline(bool $isOnline): void
    {
        AppSetting::setValue(self::WEBSITE_ONLINE, $isOnline);
    }
}
