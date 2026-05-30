<?php

namespace App\Filament\Resources\Sites\RelationManagers;

use App\Filament\Support\JsonTextEntry;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FeedsRelationManager extends RelationManager
{
    protected static string $relationship = 'feeds';

    protected static bool $shouldSkipAuthorization = true;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Feed')
                    ->schema([
                        Select::make('partner_id')
                            ->relationship('partner', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255),
                        Select::make('provider')
                            ->options([
                                'awin' => 'Awin',
                                'daisycon' => 'Daisycon',
                                'tradetracker' => 'TradeTracker',
                                'custom' => 'Custom',
                            ])
                            ->required(),
                        Toggle::make('is_active')
                            ->required()
                            ->default(true),
                    ])
                    ->columns(2),
                Section::make('Source')
                    ->schema([
                        Select::make('source_type')
                            ->options([
                                'url' => 'URL',
                                'api' => 'API',
                                'file' => 'File',
                                'manual' => 'Manual',
                            ])
                            ->required()
                            ->default('url'),
                        Textarea::make('source_url')
                            ->rows(3)
                            ->columnSpanFull(),
                        KeyValue::make('credentials')
                            ->keyLabel('Key')
                            ->valueLabel('Secret/value')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Mapping and schedule')
                    ->schema([
                        Select::make('mapping_profile_id')
                            ->relationship('mappingProfile', 'name')
                            ->searchable()
                            ->preload(),
                        TextInput::make('schedule')
                            ->placeholder('daily, hourly, weekly, or cron label')
                            ->maxLength(255),
                        KeyValue::make('mapping')
                            ->keyLabel('Override')
                            ->valueLabel('Value')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Feed')
                    ->schema([
                        TextEntry::make('partner.name')
                            ->label('Partner'),
                        TextEntry::make('name'),
                        TextEntry::make('slug'),
                        TextEntry::make('provider')
                            ->badge(),
                        TextEntry::make('source_type')
                            ->badge(),
                        TextEntry::make('mappingProfile.name')
                            ->label('Mapping profile')
                            ->placeholder('-'),
                        IconEntry::make('is_active')
                            ->boolean(),
                    ])
                    ->columns(2),
                Section::make('Source')
                    ->schema([
                        TextEntry::make('source_url')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('credentials')
                            ->label('Credentials')
                            ->formatStateUsing(fn (?array $state): string => filled($state) ? 'Configured' : 'Not configured')
                            ->placeholder('Not configured'),
                    ]),
                Section::make('Mapping and import state')
                    ->schema([
                        JsonTextEntry::make('mapping')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('schedule')
                            ->placeholder('-'),
                        TextEntry::make('last_import_status')
                            ->badge()
                            ->placeholder('Never'),
                        TextEntry::make('last_import_started_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('last_import_finished_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('last_import_message')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount([
                'products',
                'importBatches',
            ]))
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('partner.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('provider')
                    ->badge()
                    ->searchable(),
                TextColumn::make('mappingProfile.name')
                    ->label('Mapping profile')
                    ->searchable(),
                TextColumn::make('products_count')
                    ->label('Products')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('import_batches_count')
                    ->label('Imports')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('last_import_status')
                    ->badge()
                    ->placeholder('Never')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription('Deleting a feed keeps imported products but removes the feed link from them.'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
