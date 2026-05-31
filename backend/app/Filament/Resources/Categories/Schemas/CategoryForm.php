<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use App\Models\Site;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.owner'))
                    ->schema([
                        Select::make('site_id')
                            ->label(__('admin.fields.site'))
                            ->relationship('site', 'name')
                            ->live()
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('parent_id')
                            ->label(__('admin.fields.parent_category'))
                            ->options(fn (Get $get, ?Category $record = null): array => Category::query()
                                ->where('site_id', $get('site_id'))
                                ->when($record, fn ($query) => $query->whereKeyNot($record->id))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->native(false),
                    ])
                    ->columns(2),
                Section::make(__('admin.sections.content'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label(__('admin.fields.slug'))
                            ->required()
                            ->rule(fn (Get $get, ?Category $record = null) => Rule::unique('categories', 'slug')
                                ->where('site_id', $get('site_id'))
                                ->ignore($record))
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label(__('admin.fields.description'))
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make(__('admin.sections.media'))
                    ->schema([
                        FileUpload::make('hero_image')
                            ->label(__('admin.fields.hero_image'))
                            ->helperText(__('admin.helpers.category_hero_image'))
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory(fn (Get $get, ?Category $record = null): string => self::heroDirectory($get, $record))
                            ->visibility('public')
                            ->maxSize(4096)
                            ->columnSpanFull(),
                    ]),
                Section::make(__('admin.sections.seo'))
                    ->schema([
                        TextInput::make('meta_title')
                            ->label(__('admin.fields.meta_title'))
                            ->maxLength(255),
                        Textarea::make('meta_description')
                            ->label(__('admin.fields.meta_description'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make(__('admin.sections.publishing'))
                    ->schema([
                        TextInput::make('sort_order')
                            ->label(__('admin.fields.sort_order'))
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->required(),
                        Toggle::make('is_active')
                            ->label(__('admin.fields.is_active'))
                            ->default(true)
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    private static function heroDirectory(Get $get, ?Category $record = null): string
    {
        if ($record) {
            return $record->storageDirectory('hero');
        }

        $site = filled($get('site_id'))
            ? Site::query()->find($get('site_id'))
            : null;

        $categorySlug = filled($get('slug')) ? $get('slug') : 'category';

        return Site::storageDirectoryFor($site?->slug, $site?->id, "categories/{$categorySlug}/hero");
    }
}
