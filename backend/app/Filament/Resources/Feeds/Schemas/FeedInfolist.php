<?php

namespace App\Filament\Resources\Feeds\Schemas;

use App\Filament\Support\JsonTextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FeedInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Feed')
                    ->schema([
                        TextEntry::make('site.name')
                            ->label('Site'),
                        TextEntry::make('partner.name')
                            ->label('Partner'),
                        TextEntry::make('name'),
                        TextEntry::make('slug'),
                        TextEntry::make('provider')
                            ->badge(),
                        TextEntry::make('source_type')
                            ->badge(),
                        TextEntry::make('mappingProfile.name')
                            ->label('Mapping profile')
                            ->placeholder('-'),
                        IconEntry::make('is_active')
                            ->boolean(),
                    ])
                    ->columns(2),
                Section::make('Source')
                    ->schema([
                        TextEntry::make('source_url')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('credentials')
                            ->label('Credentials')
                            ->formatStateUsing(fn (?array $state): string => filled($state) ? 'Configured' : 'Not configured')
                            ->placeholder('Not configured'),
                    ]),
                Section::make('Mapping and import state')
                    ->schema([
                        JsonTextEntry::make('mapping')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('schedule')
                            ->placeholder('-'),
                        TextEntry::make('last_import_status')
                            ->badge()
                            ->placeholder('Never'),
                        TextEntry::make('last_import_started_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('last_import_finished_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('last_import_message')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Timestamps')
                    ->schema([
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
