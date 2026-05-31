<?php

namespace App\Filament\Resources\FeedImportBatches\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FeedImportBatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.import'))
                    ->schema([
                        Select::make('feed_id')
                            ->label(__('admin.fields.feed'))
                            ->relationship('feed', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('feed_mapping_profile_id')
                            ->label(__('admin.fields.mapping_profile'))
                            ->relationship('mappingProfile', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('status')
                            ->label(__('admin.fields.status'))
                            ->options(__('admin.options.import_statuses'))
                            ->required()
                            ->default('pending'),
                        Textarea::make('source_url')
                            ->label(__('admin.fields.source_url'))
                            ->columnSpanFull(),
                        DateTimePicker::make('started_at')
                            ->label(__('admin.fields.started_at')),
                        DateTimePicker::make('finished_at')
                            ->label(__('admin.fields.finished_at')),
                    ])
                    ->columns(3),
                Section::make(__('admin.sections.counters'))
                    ->schema([
                        TextInput::make('total_rows')
                            ->label(__('admin.fields.total_rows'))
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('processed_rows')
                            ->label(__('admin.fields.processed_rows'))
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('created_rows')
                            ->label(__('admin.fields.created_rows'))
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('updated_rows')
                            ->label(__('admin.fields.updated_rows'))
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('skipped_rows')
                            ->label(__('admin.fields.skipped_rows'))
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('failed_rows')
                            ->label(__('admin.fields.failed_rows'))
                            ->required()
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(3),
                Section::make(__('admin.sections.result'))
                    ->schema([
                        Textarea::make('error_message')
                            ->label(__('admin.fields.error_message'))
                            ->columnSpanFull(),
                        KeyValue::make('metrics')
                            ->label(__('admin.fields.metrics'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
