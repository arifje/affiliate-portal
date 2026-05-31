<?php

namespace App\Filament\Resources\Partners\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PartnerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.partner'))
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
                        Select::make('provider')
                            ->label(__('admin.fields.provider'))
                            ->options(__('admin.options.providers'))
                            ->required(),
                        TextInput::make('website_url')
                            ->label(__('admin.fields.website_url'))
                            ->url()
                            ->maxLength(255),
                        Toggle::make('is_active')
                            ->label(__('admin.fields.is_active'))
                            ->required()
                            ->default(true),
                    ])
                    ->columns(2),
                Section::make(__('admin.sections.settings'))
                    ->schema([
                        KeyValue::make('settings')
                            ->label(__('admin.fields.settings'))
                            ->keyLabel(__('admin.fields.key'))
                            ->valueLabel(__('admin.fields.default_value'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
