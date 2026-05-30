<?php

namespace App\Filament\Resources\Sites\Schemas;

use App\Filament\Support\JsonTextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SiteInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Site identity')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('slug'),
                        TextEntry::make('primary_domain'),
                        TextEntry::make('domain_aliases')
                            ->listWithLineBreaks()
                            ->placeholder('-')
                            ->columnSpanFull(),
                        IconEntry::make('is_active')
                            ->boolean(),
                    ])
                    ->columns(2),
                Section::make('Locale')
                    ->schema([
                        TextEntry::make('locale'),
                        TextEntry::make('currency'),
                        TextEntry::make('timezone'),
                    ])
                    ->columns(3),
                Section::make('Presentation')
                    ->schema([
                        JsonTextEntry::make('theme')
                            ->placeholder('-'),
                        JsonTextEntry::make('layout')
                            ->placeholder('-'),
                    ])
                    ->columns(2),
                Section::make('Settings')
                    ->schema([
                        JsonTextEntry::make('settings')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('created_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->dateTime()
                            ->placeholder('-'),
                    ])
                    ->columns(2),
            ]);
    }
}
