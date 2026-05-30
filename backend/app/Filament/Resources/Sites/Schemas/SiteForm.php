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
                    ->description('Optional frontend variables. Leave empty to use the default clean storefront.')
                    ->schema([
                        KeyValue::make('theme')
                            ->keyLabel('Token')
                            ->valueLabel('Value')
                            ->helperText('Useful tokens: primary_color, accent_color, background_color, surface_color, text_color, font_family.')
                            ->default([
                                'primary_color' => '#0f766e',
                                'accent_color' => '#d97706',
                                'background_color' => '#f6f8f4',
                                'surface_color' => '#ffffff',
                            ]),
                        KeyValue::make('layout')
                            ->keyLabel('Template key')
                            ->valueLabel('Template value')
                            ->helperText('Useful keys: home_variant. Supported values: clean, compact, bold.')
                            ->default([
                                'home_variant' => 'clean',
                            ]),
                    ])
                    ->columns(2),
                Section::make('Settings')
                    ->description('Optional text overrides for the shared frontend.')
                    ->schema([
                        KeyValue::make('settings')
                            ->keyLabel('Setting')
                            ->valueLabel('Value')
                            ->helperText('Useful settings: hero_title, hero_intro, hero_badge, search_placeholder, featured_title, category_title, footer_tagline.')
                            ->default([
                                'hero_badge' => 'Onafhankelijke affiliate vergelijking',
                                'search_placeholder' => 'Zoek op product, merk of categorie',
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
