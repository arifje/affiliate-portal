<?php

namespace App\Filament\Resources\CanonicalFields\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CanonicalFieldForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.canonical_field'))
                    ->schema([
                        TextInput::make('key')
                            ->label(__('admin.fields.key'))
                            ->required()
                            ->maxLength(255),
                        Select::make('field_group')
                            ->label(__('admin.fields.field_group'))
                            ->options(__('admin.options.field_groups'))
                            ->required(),
                        TextInput::make('label')
                            ->label(__('admin.fields.label'))
                            ->required()
                            ->maxLength(255),
                        Select::make('data_type')
                            ->label(__('admin.fields.data_type'))
                            ->options(__('admin.options.data_types'))
                            ->required(),
                        TextInput::make('target_column')
                            ->label(__('admin.fields.target_column'))
                            ->maxLength(255),
                        TextInput::make('metadata_path')
                            ->label(__('admin.fields.metadata_path'))
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label(__('admin.fields.description'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make(__('admin.sections.behavior'))
                    ->schema([
                        Toggle::make('is_required')
                            ->label(__('admin.fields.is_required'))
                            ->required(),
                        Toggle::make('is_searchable')
                            ->label(__('admin.fields.is_searchable'))
                            ->required(),
                        Toggle::make('is_filterable')
                            ->label(__('admin.fields.is_filterable'))
                            ->required(),
                        Toggle::make('is_variant')
                            ->label(__('admin.fields.is_variant'))
                            ->required(),
                        Toggle::make('is_active')
                            ->label(__('admin.fields.is_active'))
                            ->required()
                            ->default(true),
                        TextInput::make('sort_order')
                            ->label(__('admin.fields.sort_order'))
                            ->required()
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(3),
                Section::make(__('admin.sections.validation'))
                    ->schema([
                        KeyValue::make('validation_rules')
                            ->label(__('admin.fields.validation_rules')),
                        KeyValue::make('options')
                            ->label(__('admin.fields.options')),
                    ])
                    ->columns(2),
            ]);
    }
}
