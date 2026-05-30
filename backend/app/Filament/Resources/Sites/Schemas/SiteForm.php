<?php

namespace App\Filament\Resources\Sites\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
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
                        Actions::make([
                            Action::make('resetPresentation')
                                ->label('Reset neutral')
                                ->color('gray')
                                ->action(fn (Set $set): mixed => self::applyPresentationPreset(
                                    $set,
                                    self::neutralPresentationPreset(),
                                )),
                            Action::make('randomizePresentation')
                                ->label('Random style')
                                ->color('primary')
                                ->action(fn (Set $set): mixed => self::applyPresentationPreset(
                                    $set,
                                    self::randomPresentationPreset(),
                                )),
                        ])
                            ->columnSpanFull(),
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
                        ColorPicker::make('theme.eyebrow_color')
                            ->label('Section label color')
                            ->helperText('Used for small uppercase labels such as Onafhankelijke affiliate vergelijking, Navigatie, Aanbevolen and Catalogus.')
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
                        FileUpload::make('settings.hero_image')
                            ->label('Hero image')
                            ->helperText('Shown on the homepage hero instead of the old product/category/feed stats block.')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('site-heroes')
                            ->visibility('public')
                            ->maxSize(4096)
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

    /**
     * @param  array<string, mixed>  $preset
     */
    private static function applyPresentationPreset(Set $set, array $preset): null
    {
        foreach ($preset['theme'] as $key => $value) {
            $set("theme.{$key}", $value);
        }

        foreach ($preset['layout'] as $key => $value) {
            $set("layout.{$key}", $value);
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function neutralPresentationPreset(): array
    {
        return [
            'theme' => [
                'primary_color' => '#3f3f46',
                'primary_dark' => '#27272a',
                'accent_color' => '#0f766e',
                'eyebrow_color' => '#0f766e',
                'background_color' => '#f6f7f8',
                'muted_color' => '#e4e7eb',
                'surface_color' => '#ffffff',
                'text_color' => '#18181b',
                'soft_color' => '#eef2f3',
                'font_family' => 'Inter, ui-sans-serif, system-ui, sans-serif',
            ],
            'layout' => [
                'home_variant' => 'clean',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function randomPresentationPreset(): array
    {
        $presets = [
            [
                'theme' => [
                    'primary_color' => '#0f766e',
                    'primary_dark' => '#134e4a',
                    'accent_color' => '#d97706',
                    'eyebrow_color' => '#b45309',
                    'background_color' => '#f6f8f4',
                    'muted_color' => '#e7eee9',
                    'surface_color' => '#ffffff',
                    'text_color' => '#17211f',
                    'soft_color' => '#edf7f4',
                    'font_family' => 'Inter, ui-sans-serif, system-ui, sans-serif',
                ],
                'layout' => [
                    'home_variant' => 'clean',
                ],
            ],
            [
                'theme' => [
                    'primary_color' => '#2563eb',
                    'primary_dark' => '#1e3a8a',
                    'accent_color' => '#16a34a',
                    'eyebrow_color' => '#15803d',
                    'background_color' => '#f4f7fb',
                    'muted_color' => '#dbeafe',
                    'surface_color' => '#ffffff',
                    'text_color' => '#162033',
                    'soft_color' => '#eef5ff',
                    'font_family' => 'ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
                ],
                'layout' => [
                    'home_variant' => 'clean',
                ],
            ],
            [
                'theme' => [
                    'primary_color' => '#0e7490',
                    'primary_dark' => '#164e63',
                    'accent_color' => '#dc2626',
                    'eyebrow_color' => '#b91c1c',
                    'background_color' => '#f3fafb',
                    'muted_color' => '#cffafe',
                    'surface_color' => '#ffffff',
                    'text_color' => '#14272e',
                    'soft_color' => '#ecfeff',
                    'font_family' => 'Inter, ui-sans-serif, system-ui, sans-serif',
                ],
                'layout' => [
                    'home_variant' => 'bold',
                ],
            ],
            [
                'theme' => [
                    'primary_color' => '#475569',
                    'primary_dark' => '#1f2937',
                    'accent_color' => '#ca8a04',
                    'eyebrow_color' => '#a16207',
                    'background_color' => '#f7f7f2',
                    'muted_color' => '#e7e5dd',
                    'surface_color' => '#ffffff',
                    'text_color' => '#1f2933',
                    'soft_color' => '#f1f0e8',
                    'font_family' => 'Georgia, "Times New Roman", serif',
                ],
                'layout' => [
                    'home_variant' => 'compact',
                ],
            ],
            [
                'theme' => [
                    'primary_color' => '#be123c',
                    'primary_dark' => '#881337',
                    'accent_color' => '#0891b2',
                    'eyebrow_color' => '#0e7490',
                    'background_color' => '#fff7f6',
                    'muted_color' => '#ffe4e6',
                    'surface_color' => '#ffffff',
                    'text_color' => '#2d1720',
                    'soft_color' => '#fff1f2',
                    'font_family' => '"Trebuchet MS", Arial, sans-serif',
                ],
                'layout' => [
                    'home_variant' => 'bold',
                ],
            ],
        ];

        return $presets[array_rand($presets)];
    }
}
