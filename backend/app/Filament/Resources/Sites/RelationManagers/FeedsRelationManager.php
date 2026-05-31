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
                Section::make(__('admin.sections.feed'))
                    ->schema([
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

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.feed'))
                    ->schema([
                        TextEntry::make('partner.name')
                            ->label(__('admin.fields.partner')),
                        TextEntry::make('name')
                            ->label(__('admin.fields.name')),
                        TextEntry::make('slug')
                            ->label(__('admin.fields.slug')),
                        TextEntry::make('provider')
                            ->label(__('admin.fields.provider'))
                            ->badge(),
                        TextEntry::make('source_type')
                            ->label(__('admin.fields.source_type'))
                            ->badge(),
                        TextEntry::make('mappingProfile.name')
                            ->label(__('admin.fields.mapping_profile'))
                            ->placeholder('-'),
                        IconEntry::make('is_active')
                            ->label(__('admin.fields.is_active'))
                            ->boolean(),
                    ])
                    ->columns(2),
                Section::make(__('admin.sections.source'))
                    ->schema([
                        TextEntry::make('source_url')
                            ->label(__('admin.fields.source_url'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('credentials')
                            ->label(__('admin.fields.credentials'))
                            ->formatStateUsing(fn (?array $state): string => filled($state) ? __('admin.messages.configured') : __('admin.messages.not_configured'))
                            ->placeholder(__('admin.messages.not_configured')),
                    ]),
                Section::make(__('admin.sections.mapping_and_import_state'))
                    ->schema([
                        JsonTextEntry::make('mapping')
                            ->label(__('admin.fields.mapping'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('schedule')
                            ->label(__('admin.fields.schedule'))
                            ->placeholder('-'),
                        TextEntry::make('last_import_status')
                            ->label(__('admin.fields.last_import_status'))
                            ->badge()
                            ->placeholder(__('admin.placeholders.never')),
                        TextEntry::make('last_import_started_at')
                            ->label(__('admin.fields.last_import_started_at'))
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('last_import_finished_at')
                            ->label(__('admin.fields.last_import_finished_at'))
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('last_import_message')
                            ->label(__('admin.fields.last_import_message'))
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
                    ->label(__('admin.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('partner.name')
                    ->label(__('admin.fields.partner'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('provider')
                    ->label(__('admin.fields.provider'))
                    ->badge()
                    ->searchable(),
                TextColumn::make('mappingProfile.name')
                    ->label(__('admin.fields.mapping_profile'))
                    ->searchable(),
                TextColumn::make('products_count')
                    ->label(__('admin.fields.products'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('import_batches_count')
                    ->label(__('admin.fields.imports'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('last_import_status')
                    ->label(__('admin.fields.last_import_status'))
                    ->badge()
                    ->placeholder(__('admin.placeholders.never'))
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label(__('admin.fields.is_active'))
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label(__('admin.fields.updated_at'))
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
                    ->modalDescription(__('admin.messages.deleting_feed')),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
