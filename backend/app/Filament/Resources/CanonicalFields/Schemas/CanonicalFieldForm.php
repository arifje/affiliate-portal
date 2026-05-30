<?php

namespace App\Filament\Resources\CanonicalFields\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CanonicalFieldForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Canonical field')
                    ->schema([
                        TextInput::make('key')
                            ->required()
                            ->maxLength(255),
                        Select::make('field_group')
                            ->options([
                                'identity' => 'Identity',
                                'content' => 'Content',
                                'urls' => 'URLs',
                                'pricing' => 'Pricing',
                                'availability' => 'Availability',
                                'variants' => 'Variants',
                                'classification' => 'Classification',
                                'compliance' => 'Compliance',
                            ])
                            ->required(),
                        TextInput::make('label')
                            ->required()
                            ->maxLength(255),
                        Select::make('data_type')
                            ->options([
                                'array' => 'Array',
                                'boolean' => 'Boolean',
                                'date' => 'Date',
                                'decimal' => 'Decimal',
                                'integer' => 'Integer',
                                'json' => 'JSON',
                                'string' => 'String',
                                'text' => 'Text',
                                'url' => 'URL',
                            ])
                            ->required(),
                        TextInput::make('target_column')
                            ->maxLength(255),
                        TextInput::make('metadata_path')
                            ->maxLength(255),
                        Textarea::make('description')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Behavior')
                    ->schema([
                        Toggle::make('is_required')
                            ->required(),
                        Toggle::make('is_searchable')
                            ->required(),
                        Toggle::make('is_filterable')
                            ->required(),
                        Toggle::make('is_variant')
                            ->required(),
                        Toggle::make('is_active')
                            ->required()
                            ->default(true),
                        TextInput::make('sort_order')
                            ->required()
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(3),
                Section::make('Validation')
                    ->schema([
                        KeyValue::make('validation_rules'),
                        KeyValue::make('options'),
                    ])
                    ->columns(2),
            ]);
    }
}
