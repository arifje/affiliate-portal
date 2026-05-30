<?php

namespace App\Filament\Resources\FeedFieldMappings\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FeedFieldMappingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Mapping')
                    ->schema([
                        Select::make('feed_mapping_profile_id')
                            ->relationship('profile', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('canonical_field_id')
                            ->relationship('canonicalField', 'key')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('source_field')
                            ->maxLength(255),
                        Textarea::make('source_path')
                            ->columnSpanFull(),
                        TagsInput::make('fallback_fields')
                            ->columnSpanFull(),
                        Textarea::make('default_value')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Transform')
                    ->schema([
                        Select::make('transform_type')
                            ->options([
                                'copy' => 'Copy',
                                'trim' => 'Trim',
                                'lowercase' => 'Lowercase',
                                'uppercase' => 'Uppercase',
                                'money' => 'Money',
                                'decimal' => 'Decimal',
                                'integer' => 'Integer',
                                'boolean' => 'Boolean',
                                'availability' => 'Availability',
                                'array' => 'Array',
                                'url' => 'URL',
                            ])
                            ->required()
                            ->default('copy'),
                        TextInput::make('sort_order')
                            ->required()
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_required')
                            ->required(),
                        KeyValue::make('transform_config')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ]);
    }
}
