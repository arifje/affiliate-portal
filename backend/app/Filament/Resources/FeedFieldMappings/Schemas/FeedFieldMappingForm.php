<?php

namespace App\Filament\Resources\FeedFieldMappings\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FeedFieldMappingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.mapping'))
                    ->schema([
                        Select::make('feed_mapping_profile_id')
                            ->label(__('admin.fields.mapping_profile'))
                            ->relationship('profile', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('canonical_field_id')
                            ->label(__('admin.fields.canonical_field'))
                            ->relationship('canonicalField', 'key')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('source_field')
                            ->label(__('admin.fields.source_field'))
                            ->maxLength(255),
                        Textarea::make('source_path')
                            ->label(__('admin.fields.source_path'))
                            ->columnSpanFull(),
                        TagsInput::make('fallback_fields')
                            ->label(__('admin.fields.fallback_fields'))
                            ->columnSpanFull(),
                        Textarea::make('default_value')
                            ->label(__('admin.fields.default_value'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make(__('admin.sections.transform'))
                    ->schema([
                        Select::make('transform_type')
                            ->label(__('admin.fields.transform_type'))
                            ->options(__('admin.options.transform_types'))
                            ->required()
                            ->default('copy'),
                        TextInput::make('sort_order')
                            ->label(__('admin.fields.sort_order'))
                            ->required()
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_required')
                            ->label(__('admin.fields.is_required'))
                            ->required(),
                        KeyValue::make('transform_config')
                            ->label(__('admin.fields.transform_config'))
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ]);
    }
}
