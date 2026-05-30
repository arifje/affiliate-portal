<?php

namespace App\Filament\Resources\Sites\Schemas;

use Filament\Infolists\Components\CodeEntry;
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
                        CodeEntry::make('theme')
                            ->grammar('json')
                            ->placeholder('-'),
                        CodeEntry::make('layout')
                            ->grammar('json')
                            ->placeholder('-'),
                    ])
                    ->columns(2),
                Section::make('Settings')
                    ->schema([
                        CodeEntry::make('settings')
                            ->grammar('json')
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
