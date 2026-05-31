<?php

namespace App\Filament\Resources\Sites\Schemas;

use App\Models\Site;
use DateTimeZone;
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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class SiteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.site_identity'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label(__('admin.fields.slug'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('primary_domain')
                            ->label(__('admin.fields.primary_domain'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('maskers.nl')
                            ->maxLength(255),
                        TagsInput::make('domain_aliases')
                            ->label(__('admin.fields.domain_aliases'))
                            ->placeholder('www.maskers.nl')
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label(__('admin.fields.is_active'))
                            ->required()
                            ->default(true),
                    ])
                    ->columns(2),
                Section::make(__('admin.sections.locale'))
                    ->schema([
                        TextInput::make('locale')
                            ->label(__('admin.fields.locale'))
                            ->required()
                            ->default('nl_NL')
                            ->maxLength(12),
                        TextInput::make('currency')
                            ->label(__('admin.fields.currency'))
                            ->required()
                            ->default('EUR')
                            ->maxLength(3),
                        Select::make('timezone')
                            ->label(__('admin.fields.timezone'))
                            ->required()
                            ->default('Europe/Amsterdam')
                            ->options(self::timezoneOptions())
                            ->searchable()
                            ->preload()
                            ->native(false),
                    ])
                    ->columns(3),
                Section::make(__('admin.sections.presentation'))
                    ->description(__('admin.pages.sites.presentation_description'))
                    ->schema([
                        Actions::make([
                            Action::make('resetPresentation')
                                ->label(__('admin.pages.sites.actions.reset_neutral'))
                                ->color('gray')
                                ->action(fn (Set $set): mixed => self::applyPresentationPreset(
                                    $set,
                                    self::neutralPresentationPreset(),
                                )),
                            Action::make('randomizePresentation')
                                ->label(__('admin.pages.sites.actions.random_style'))
                                ->color('primary')
                                ->action(fn (Set $set): mixed => self::applyPresentationPreset(
                                    $set,
                                    self::randomPresentationPreset(),
                                )),
                        ])
                            ->columnSpanFull(),
                        ColorPicker::make('theme.primary_color')
                            ->label(__('admin.pages.sites.theme.primary_color'))
                            ->hex()
                            ->default('#0f766e'),
                        ColorPicker::make('theme.primary_dark')
                            ->label(__('admin.pages.sites.theme.primary_dark'))
                            ->hex()
                            ->default('#134e4a'),
                        ColorPicker::make('theme.accent_color')
                            ->label(__('admin.pages.sites.theme.accent_color'))
                            ->hex()
                            ->default('#d97706'),
                        ColorPicker::make('theme.eyebrow_color')
                            ->label(__('admin.pages.sites.theme.eyebrow_color'))
                            ->helperText(__('admin.helpers.section_label_color'))
                            ->hex()
                            ->default('#d97706'),
                        ColorPicker::make('theme.background_color')
                            ->label(__('admin.pages.sites.theme.background_color'))
                            ->hex()
                            ->default('#f6f8f4'),
                        ColorPicker::make('theme.muted_color')
                            ->label(__('admin.pages.sites.theme.muted_color'))
                            ->hex()
                            ->default('#e7eee9'),
                        ColorPicker::make('theme.surface_color')
                            ->label(__('admin.pages.sites.theme.surface_color'))
                            ->hex()
                            ->default('#ffffff'),
                        ColorPicker::make('theme.text_color')
                            ->label(__('admin.pages.sites.theme.text_color'))
                            ->hex()
                            ->default('#17211f'),
                        ColorPicker::make('theme.soft_color')
                            ->label(__('admin.pages.sites.theme.soft_color'))
                            ->hex()
                            ->default('#edf7f4'),
                        Select::make('theme.font_family')
                            ->label(__('admin.pages.sites.theme.font_family'))
                            ->options(__('admin.options.font_families'))
                            ->default('Inter, ui-sans-serif, system-ui, sans-serif'),
                        Select::make('layout.home_variant')
                            ->label(__('admin.pages.sites.theme.home_variant'))
                            ->options(__('admin.options.homepage_variants'))
                            ->default('clean'),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),
                Section::make(__('admin.sections.settings'))
                    ->description(__('admin.pages.sites.settings_description'))
                    ->schema([
                        TextInput::make('settings.hero_badge')
                            ->label(__('admin.pages.sites.content.hero_badge'))
                            ->default('Onafhankelijke affiliate vergelijking')
                            ->maxLength(255),
                        TextInput::make('settings.hero_title')
                            ->label(__('admin.pages.sites.content.hero_title'))
                            ->maxLength(255),
                        Textarea::make('settings.hero_intro')
                            ->label(__('admin.pages.sites.content.hero_intro'))
                            ->rows(3)
                            ->columnSpanFull(),
                        FileUpload::make('settings.hero_image')
                            ->label(__('admin.pages.sites.content.hero_image'))
                            ->helperText(__('admin.helpers.site_hero_image'))
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->imageEditor()
                            ->disk('public')
                            ->directory(fn (Get $get, ?Site $record = null): string => Site::storageDirectoryFor(
                                $record?->slug ?: $get('slug'),
                                $record?->id,
                                'hero',
                            ))
                            ->visibility('public')
                            ->maxSize(10240)
                            ->columnSpanFull(),
                        FileUpload::make('settings.products_hero_image')
                            ->label(__('admin.pages.sites.content.products_hero_image'))
                            ->helperText(__('admin.helpers.products_hero_image'))
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->imageEditor()
                            ->disk('public')
                            ->directory(fn (Get $get, ?Site $record = null): string => Site::storageDirectoryFor(
                                $record?->slug ?: $get('slug'),
                                $record?->id,
                                'products-hero',
                            ))
                            ->visibility('public')
                            ->maxSize(10240)
                            ->columnSpanFull(),
                        TextInput::make('settings.search_placeholder')
                            ->label(__('admin.pages.sites.content.search_placeholder'))
                            ->default('Zoek op product, merk of categorie')
                            ->maxLength(255),
                        TextInput::make('settings.featured_title')
                            ->label(__('admin.pages.sites.content.featured_title'))
                            ->maxLength(255),
                        TextInput::make('settings.category_title')
                            ->label(__('admin.pages.sites.content.category_title'))
                            ->maxLength(255),
                        TextInput::make('settings.footer_tagline')
                            ->label(__('admin.pages.sites.content.footer_tagline'))
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
     * @return array<string, string>
     */
    private static function timezoneOptions(): array
    {
        return collect(DateTimeZone::listIdentifiers())
            ->mapWithKeys(fn (string $timezone): array => [$timezone => $timezone])
            ->all();
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
