<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.content'))
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('admin.fields.name')),
                        TextEntry::make('slug')
                            ->label(__('admin.fields.slug')),
                        TextEntry::make('site.name')
                            ->label(__('admin.fields.site')),
                        TextEntry::make('parent.name')
                            ->label(__('admin.fields.parent_category'))
                            ->placeholder('-'),
                        TextEntry::make('description')
                            ->label(__('admin.fields.description'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make(__('admin.sections.media'))
                    ->schema([
                        ImageEntry::make('hero_image')
                            ->label(__('admin.fields.hero_image'))
                            ->disk('public')
                            ->visibility('public')
                            ->imageHeight(220)
                            ->placeholder(__('admin.placeholders.no_hero_image'))
                            ->columnSpanFull(),
                    ]),
                Section::make(__('admin.sections.seo'))
                    ->schema([
                        TextEntry::make('meta_title')
                            ->label(__('admin.fields.meta_title'))
                            ->placeholder('-'),
                        TextEntry::make('meta_description')
                            ->label(__('admin.fields.meta_description'))
                            ->placeholder('-'),
                    ])
                    ->columns(2),
                Section::make(__('admin.sections.publishing'))
                    ->schema([
                        TextEntry::make('sort_order')
                            ->label(__('admin.fields.sort_order')),
                        IconEntry::make('is_active')
                            ->label(__('admin.fields.is_active'))
                            ->boolean(),
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
