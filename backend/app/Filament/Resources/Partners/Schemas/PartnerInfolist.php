<?php

namespace App\Filament\Resources\Partners\Schemas;

use App\Filament\Support\JsonTextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PartnerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Partner')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('slug'),
                        TextEntry::make('provider')
                            ->badge(),
                        TextEntry::make('website_url')
                            ->placeholder('-'),
                        IconEntry::make('is_active')
                            ->boolean(),
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
