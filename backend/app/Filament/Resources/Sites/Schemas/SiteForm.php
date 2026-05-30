<?php

namespace App\Filament\Resources\Sites\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
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
                    ->description('Theme and layout controls for the shared storefront.')
                    ->schema([
                        ColorPicker::make('theme.primary_color')
                            ->label('Primary color')
                            ->hex()
                            ->default('#0f766e'),
                        ColorPicker::make('theme.primary_dark')
                            ->label('Primary dark')
                            ->hex()
                            ->default('#134e4a'),
                        ColorPicker::make('theme.accent_color')
                            ->label('Accent color')
                            ->hex()
                            ->default('#d97706'),
                        ColorPicker::make('theme.background_color')
                            ->label('Background color')
                            ->hex()
                            ->default('#f6f8f4'),
                        ColorPicker::make('theme.muted_color')
                            ->label('Muted color')
                            ->hex()
                            ->default('#e7eee9'),
                        ColorPicker::make('theme.surface_color')
                            ->label('Surface color')
                            ->hex()
                            ->default('#ffffff'),
                        ColorPicker::make('theme.text_color')
                            ->label('Text color')
                            ->hex()
                            ->default('#17211f'),
                        ColorPicker::make('theme.soft_color')
                            ->label('Soft background')
                            ->hex()
                            ->default('#edf7f4'),
                        Select::make('theme.font_family')
                            ->label('Font')
                            ->options([
                                'Inter, ui-sans-serif, system-ui, sans-serif' => 'Inter / modern sans',
                                'ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif' => 'System sans',
                                'Arial, Helvetica, sans-serif' => 'Arial / neutral sans',
                                '"Trebuchet MS", Arial, sans-serif' => 'Trebuchet / friendly sans',
                                'Georgia, "Times New Roman", serif' => 'Georgia / editorial serif',
                            ])
                            ->default('Inter, ui-sans-serif, system-ui, sans-serif'),
                        Select::make('layout.home_variant')
                            ->label('Homepage variant')
                            ->options([
                                'clean' => 'Clean',
                                'compact' => 'Compact',
                                'bold' => 'Bold',
                            ])
                            ->default('clean'),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),
                Section::make('Settings')
                    ->description('Text overrides for the shared storefront.')
                    ->schema([
                        TextInput::make('settings.hero_badge')
                            ->label('Hero badge')
                            ->default('Onafhankelijke affiliate vergelijking')
                            ->maxLength(255),
                        TextInput::make('settings.hero_title')
                            ->label('Hero title')
                            ->maxLength(255),
                        Textarea::make('settings.hero_intro')
                            ->label('Hero intro')
                            ->rows(3)
                            ->columnSpanFull(),
                        TextInput::make('settings.search_placeholder')
                            ->label('Search placeholder')
                            ->default('Zoek op product, merk of categorie')
                            ->maxLength(255),
                        TextInput::make('settings.featured_title')
                            ->label('Featured section title')
                            ->maxLength(255),
                        TextInput::make('settings.category_title')
                            ->label('Category section title')
                            ->maxLength(255),
                        TextInput::make('settings.footer_tagline')
                            ->label('Footer tagline')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
