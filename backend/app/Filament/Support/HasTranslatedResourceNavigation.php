<?php

namespace App\Filament\Support;

use UnitEnum;

trait HasTranslatedResourceNavigation
{
    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __(static::$navigationGroupTranslationKey);
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.'.static::$translationKey.'.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.'.static::$translationKey.'.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.'.static::$translationKey.'.navigation_label');
    }
}
