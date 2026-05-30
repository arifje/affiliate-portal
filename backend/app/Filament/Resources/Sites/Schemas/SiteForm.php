<?php

namespace App\Filament\Resources\Sites\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SiteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Site identity')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('primary_domain')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('maskers.nl')
                            ->maxLength(255),
                        TagsInput::make('domain_aliases')
                            ->placeholder('www.maskers.nl')
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->required()
                            ->default(true),
                    ])
                    ->columns(2),
                Section::make('Locale')
                    ->schema([
                        TextInput::make('locale')
                            ->required()
                            ->default('nl_NL')
                            ->maxLength(12),
                        TextInput::make('currency')
                            ->required()
                            ->default('EUR')
                            ->maxLength(3),
                        TextInput::make('timezone')
                            ->required()
                            ->default('Europe/Amsterdam')
                            ->maxLength(255),
                    ])
                    ->columns(3),
                Section::make('Presentation')
                    ->schema([
                        KeyValue::make('theme')
                            ->keyLabel('Token')
                            ->valueLabel('Value'),
                        KeyValue::make('layout')
                            ->keyLabel('Template key')
                            ->valueLabel('Template value'),
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
