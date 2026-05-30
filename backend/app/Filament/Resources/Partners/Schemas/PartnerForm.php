<?php

namespace App\Filament\Resources\Partners\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PartnerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Partner')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Select::make('provider')
                            ->options([
                                'awin' => 'Awin',
                                'daisycon' => 'Daisycon',
                                'tradetracker' => 'TradeTracker',
                                'custom' => 'Custom',
                            ])
                            ->required(),
                        TextInput::make('website_url')
                            ->url()
                            ->maxLength(255),
                        Toggle::make('is_active')
                            ->required()
                            ->default(true),
                    ])
                    ->columns(2),
                Section::make('Settings')
                    ->schema([
                        KeyValue::make('settings')
                            ->keyLabel('Setting')
                            ->valueLabel('Value')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
