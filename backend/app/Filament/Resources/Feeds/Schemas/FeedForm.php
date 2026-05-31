<?php

namespace App\Filament\Resources\Feeds\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FeedForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.feed'))
                    ->schema([
                        Select::make('site_id')
                            ->label(__('admin.fields.site'))
                            ->relationship('site', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('partner_id')
                            ->label(__('admin.fields.partner'))
                            ->relationship('partner', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
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
                        Toggle::make('is_active')
                            ->label(__('admin.fields.is_active'))
                            ->required()
                            ->default(true),
                    ])
                    ->columns(2),
                Section::make(__('admin.sections.source'))
                    ->schema([
                        Select::make('source_type')
                            ->label(__('admin.fields.source_type'))
                            ->options(__('admin.options.source_types'))
                            ->required()
                            ->default('url'),
                        Textarea::make('source_url')
                            ->label(__('admin.fields.source_url'))
                            ->rows(3)
                            ->columnSpanFull(),
                        KeyValue::make('credentials')
                            ->label(__('admin.fields.credentials'))
                            ->keyLabel(__('admin.fields.key'))
                            ->valueLabel(__('admin.fields.default_value'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make(__('admin.sections.mapping_and_schedule'))
                    ->schema([
                        Select::make('mapping_profile_id')
                            ->label(__('admin.fields.mapping_profile'))
                            ->relationship('mappingProfile', 'name')
                            ->searchable()
                            ->preload(),
                        TextInput::make('schedule')
                            ->label(__('admin.fields.schedule'))
                            ->placeholder(__('admin.placeholders.schedule'))
                            ->maxLength(255),
                        KeyValue::make('mapping')
                            ->label(__('admin.fields.mapping'))
                            ->keyLabel(__('admin.fields.key'))
                            ->valueLabel(__('admin.fields.default_value'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
