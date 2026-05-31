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
                Section::make(__('admin.sections.profile'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label(__('admin.fields.slug'))
                            ->required()
                            ->maxLength(255),
                        Select::make('provider')
                            ->label(__('admin.fields.provider'))
                            ->options(__('admin.options.providers'))
                            ->required(),
                        Select::make('source_format')
                            ->label(__('admin.fields.source_format'))
                            ->options(__('admin.options.source_formats'))
                            ->required()
                            ->default('csv'),
                        Select::make('site_id')
                            ->label(__('admin.fields.site'))
                            ->relationship('site', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('partner_id')
                            ->label(__('admin.fields.partner'))
                            ->relationship('partner', 'name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),
                Section::make(__('admin.sections.parsing'))
                    ->schema([
                        TextInput::make('source_encoding')
                            ->label(__('admin.fields.source_encoding'))
                            ->required()
                            ->default('utf-8')
                            ->maxLength(255),
                        TextInput::make('delimiter')
                            ->label(__('admin.fields.delimiter'))
                            ->maxLength(8),
                        TextInput::make('enclosure')
                            ->label(__('admin.fields.enclosure'))
                            ->maxLength(8),
                        TextInput::make('decimal_separator')
                            ->label(__('admin.fields.decimal_separator'))
                            ->required()
                            ->default('.')
                            ->maxLength(4),
                        TextInput::make('thousands_separator')
                            ->label(__('admin.fields.thousands_separator'))
                            ->maxLength(4),
                        TextInput::make('row_selector')
                            ->label(__('admin.fields.row_selector'))
                            ->maxLength(255),
                        Toggle::make('first_row_is_header')
                            ->label(__('admin.fields.first_row_is_header'))
                            ->required()
                            ->default(true),
                    ])
                    ->columns(3),
                Section::make(__('admin.sections.defaults'))
                    ->schema([
                        TextInput::make('currency')
                            ->label(__('admin.fields.currency'))
                            ->required()
                            ->default('EUR')
                            ->maxLength(3),
                        TextInput::make('locale')
                            ->label(__('admin.fields.locale'))
                            ->required()
                            ->default('nl_NL')
                            ->maxLength(12),
                        TextInput::make('timezone')
                            ->label(__('admin.fields.timezone'))
                            ->required()
                            ->default('Europe/Amsterdam')
                            ->maxLength(255),
                        Toggle::make('is_template')
                            ->label(__('admin.fields.is_template'))
                            ->required(),
                        Toggle::make('is_active')
                            ->label(__('admin.fields.is_active'))
                            ->required()
                            ->default(true),
                    ])
                    ->columns(3),
                Section::make(__('admin.sections.settings'))
                    ->schema([
                        KeyValue::make('settings')
                            ->label(__('admin.fields.settings'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
