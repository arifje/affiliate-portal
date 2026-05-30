<?php

namespace App\Filament\Support;

use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontFamily;

class JsonTextEntry
{
    public static function make(string $name): TextEntry
    {
        return TextEntry::make($name)
            ->formatStateUsing(fn (mixed $state): ?string => self::format($state))
            ->fontFamily(FontFamily::Mono)
            ->copyable();
    }

    private static function format(mixed $state): ?string
    {
        if (blank($state)) {
            return null;
        }

        if (is_string($state)) {
            $decoded = json_decode($state, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $state;
            }

            $state = $decoded;
        }

        return json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: null;
    }
}
