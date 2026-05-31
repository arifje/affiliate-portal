<?php

namespace App\Filament\Resources\Sites\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SiteInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.site_identity'))
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('admin.fields.name')),
                        TextEntry::make('slug')
                            ->label(__('admin.fields.slug')),
                        TextEntry::make('primary_domain')
                            ->label(__('admin.fields.primary_domain')),
                        TextEntry::make('domain_aliases')
                            ->label(__('admin.fields.domain_aliases'))
                            ->listWithLineBreaks()
                            ->placeholder('-')
                            ->columnSpanFull(),
                        IconEntry::make('is_active')
                            ->label(__('admin.fields.is_active'))
                            ->boolean(),
                    ])
                    ->columns(2),
                Section::make(__('admin.sections.locale'))
                    ->schema([
                        TextEntry::make('locale')
                            ->label(__('admin.fields.locale')),
                        TextEntry::make('currency')
                            ->label(__('admin.fields.currency')),
                        TextEntry::make('timezone')
                            ->label(__('admin.fields.timezone')),
                    ])
                    ->columns(3),
                Section::make(__('admin.sections.presentation'))
                    ->schema([
                        KeyValueEntry::make('theme')
                            ->label(__('admin.fields.theme'))
                            ->keyLabel(__('admin.fields.key'))
                            ->valueLabel(__('admin.fields.default_value'))
                            ->placeholder(__('admin.placeholders.no_theme_values')),
                        KeyValueEntry::make('layout')
                            ->label(__('admin.pages.sites.theme.home_variant'))
                            ->keyLabel(__('admin.fields.key'))
                            ->valueLabel(__('admin.fields.default_value'))
                            ->placeholder(__('admin.placeholders.no_layout_values')),
                    ])
                    ->columns(2),
                Section::make(__('admin.sections.settings'))
                    ->schema([
                        ImageEntry::make('settings.hero_image')
                            ->label(__('admin.pages.sites.content.hero_image'))
                            ->disk('public')
                            ->visibility('public')
                            ->imageHeight(180)
                            ->placeholder(__('admin.placeholders.no_hero_image'))
                            ->columnSpanFull(),
                        KeyValueEntry::make('settings')
                            ->label(__('admin.fields.homepage_content'))
                            ->keyLabel(__('admin.fields.key'))
                            ->valueLabel(__('admin.fields.default_value'))
                            ->placeholder(__('admin.placeholders.no_homepage_settings'))
                            ->columnSpanFull(),
                        TextEntry::make('created_at')
                            ->label(__('admin.fields.created_at'))
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->label(__('admin.fields.updated_at'))
                            ->dateTime()
                            ->placeholder('-'),
                    ])
                    ->columns(2),
            ]);
    }
}
