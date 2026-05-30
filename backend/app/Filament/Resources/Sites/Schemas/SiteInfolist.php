<?php

namespace App\Filament\Resources\Sites\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\KeyValueEntry;
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
                        KeyValueEntry::make('theme')
                            ->keyLabel('Token')
                            ->valueLabel('Value')
                            ->placeholder('No theme values set.'),
                        KeyValueEntry::make('layout')
                            ->keyLabel('Setting')
                            ->valueLabel('Value')
                            ->placeholder('No layout values set.'),
                    ])
                    ->columns(2),
                Section::make('Settings')
                    ->schema([
                        ImageEntry::make('settings.hero_image')
                            ->label('Hero image')
                            ->disk('public')
                            ->visibility('public')
                            ->imageHeight(180)
                            ->placeholder('No hero image uploaded.')
                            ->columnSpanFull(),
                        KeyValueEntry::make('settings')
                            ->label('Homepage content')
                            ->keyLabel('Setting')
                            ->valueLabel('Value')
                            ->placeholder('No homepage settings set.')
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
