<?php

namespace App\Filament\Resources\FeedImportBatches\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FeedImportBatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Import')
                    ->schema([
                        Select::make('feed_id')
                            ->relationship('feed', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('feed_mapping_profile_id')
                            ->relationship('mappingProfile', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'running' => 'Running',
                                'completed' => 'Completed',
                                'failed' => 'Failed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('pending'),
                        Textarea::make('source_url')
                            ->columnSpanFull(),
                        DateTimePicker::make('started_at'),
                        DateTimePicker::make('finished_at'),
                    ])
                    ->columns(3),
                Section::make('Counters')
                    ->schema([
                        TextInput::make('total_rows')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('processed_rows')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('created_rows')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('updated_rows')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('skipped_rows')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('failed_rows')
                            ->required()
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(3),
                Section::make('Result')
                    ->schema([
                        Textarea::make('error_message')
                            ->columnSpanFull(),
                        KeyValue::make('metrics')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
