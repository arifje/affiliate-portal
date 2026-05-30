<?php

namespace App\Filament\Support;

use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontFamily;
use Illuminate\Database\Eloquent\Model;

class JsonTextEntry
{
    public static function make(string $name): TextEntry
    {
        return TextEntry::make($name)
            ->state(fn (?Model $record): ?string => self::format(data_get($record, $name)))
            ->fontFamily(FontFamily::Mono)
            ->extraAttributes(['style' => 'white-space: pre-wrap; word-break: break-word;'])
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
