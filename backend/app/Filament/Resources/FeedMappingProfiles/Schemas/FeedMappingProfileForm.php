<?php

namespace App\Filament\Resources\FeedMappingProfiles\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FeedMappingProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profile')
                    ->schema([
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
                        Select::make('source_format')
                            ->options([
                                'csv' => 'CSV',
                                'json' => 'JSON',
                                'jsonl' => 'JSONL',
                                'xml' => 'XML',
                            ])
                            ->required()
                            ->default('csv'),
                        Select::make('site_id')
                            ->relationship('site', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('partner_id')
                            ->relationship('partner', 'name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),
                Section::make('Parsing')
                    ->schema([
                        TextInput::make('source_encoding')
                            ->required()
                            ->default('utf-8')
                            ->maxLength(255),
                        TextInput::make('delimiter')
                            ->maxLength(8),
                        TextInput::make('enclosure')
                            ->maxLength(8),
                        TextInput::make('decimal_separator')
                            ->required()
                            ->default('.')
                            ->maxLength(4),
                        TextInput::make('thousands_separator')
                            ->maxLength(4),
                        TextInput::make('row_selector')
                            ->maxLength(255),
                        Toggle::make('first_row_is_header')
                            ->required()
                            ->default(true),
                    ])
                    ->columns(3),
                Section::make('Defaults')
                    ->schema([
                        TextInput::make('currency')
                            ->required()
                            ->default('EUR')
                            ->maxLength(3),
                        TextInput::make('locale')
                            ->required()
                            ->default('nl_NL')
                            ->maxLength(12),
                        TextInput::make('timezone')
                            ->required()
                            ->default('Europe/Amsterdam')
                            ->maxLength(255),
                        Toggle::make('is_template')
                            ->required(),
                        Toggle::make('is_active')
                            ->required()
                            ->default(true),
                    ])
                    ->columns(3),
                Section::make('Settings')
                    ->schema([
                        KeyValue::make('settings')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
