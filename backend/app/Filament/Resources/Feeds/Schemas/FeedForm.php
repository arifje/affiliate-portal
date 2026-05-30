<?php

namespace App\Filament\Resources\Feeds\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FeedForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Feed')
                    ->schema([
                        Select::make('site_id')
                            ->relationship('site', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('partner_id')
                            ->relationship('partner', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255),
                        Select::make('provider')
                            ->options([
                                'awin' => 'Awin',
                                'daisycon' => 'Daisycon',
                                'tradetracker' => 'TradeTracker',
                                'custom' => 'Custom',
                            ])
                            ->required(),
                        Toggle::make('is_active')
                            ->required()
                            ->default(true),
                    ])
                    ->columns(2),
                Section::make('Source')
                    ->schema([
                        Select::make('source_type')
                            ->options([
                                'url' => 'URL',
                                'api' => 'API',
                                'file' => 'File',
                                'manual' => 'Manual',
                            ])
                            ->required()
                            ->default('url'),
                        Textarea::make('source_url')
                            ->rows(3)
                            ->columnSpanFull(),
                        KeyValue::make('credentials')
                            ->keyLabel('Key')
                            ->valueLabel('Secret/value')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Mapping and schedule')
                    ->schema([
                        Select::make('mapping_profile_id')
                            ->relationship('mappingProfile', 'name')
                            ->searchable()
                            ->preload(),
                        TextInput::make('schedule')
                            ->placeholder('daily, hourly, weekly, or cron label')
                            ->maxLength(255),
                        KeyValue::make('mapping')
                            ->keyLabel('Override')
                            ->valueLabel('Value')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
