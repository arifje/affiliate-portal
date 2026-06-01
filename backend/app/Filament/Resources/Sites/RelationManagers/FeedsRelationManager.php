<?php

namespace App\Filament\Resources\Sites\RelationManagers;

use App\Models\CanonicalField;
use App\Models\Partner;
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
use Filament\Schemas\Components\Utilities\Get;
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
                            ->options(fn (Get $get): array => Partner::query()
                                ->when($get('provider'), fn ($query, string $provider) => $query->where('provider', $provider))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
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
                            ->label(__('admin.fields.platform'))
                            ->options(__('admin.options.providers'))
                            ->live()
                            ->native(false)
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
                            ->live()
                            ->required()
                            ->default('url'),
                        Select::make('source_format')
                            ->label(__('admin.fields.source_format'))
                            ->options(__('admin.options.source_formats'))
                            ->required()
                            ->default('csv'),
                        Textarea::make('source_url')
                            ->label(__('admin.fields.source_url'))
                            ->visible(fn (Get $get): bool => in_array($get('source_type'), ['url', 'api'], true))
                            ->required(fn (Get $get): bool => in_array($get('source_type'), ['url', 'api'], true))
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
                        Select::make('unique_identifier_field')
                            ->label(__('admin.fields.unique_identifier_field'))
                            ->options(fn (): array => CanonicalField::query()
                                ->active()
                                ->orderBy('field_group')
                                ->orderBy('sort_order')
                                ->orderBy('label')
                                ->pluck('label', 'key')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->placeholder('external_id'),
                        Select::make('schedule')
                            ->label(__('admin.fields.schedule'))
                            ->options(__('admin.options.feed_schedules'))
                            ->placeholder(__('admin.placeholders.schedule'))
                            ->native(false),
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
                            ->label(__('admin.fields.platform'))
                            ->badge(),
                        TextEntry::make('source_type')
                            ->label(__('admin.fields.source_type'))
                            ->badge(),
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
                        TextEntry::make('unique_identifier_field')
                            ->label(__('admin.fields.unique_identifier_field'))
                            ->placeholder('external_id'),
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
                    ->label(__('admin.fields.platform'))
                    ->badge()
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
                    ->modal(false)
                    ->requiresConfirmation(false)
                    ->extraAttributes(['wire:confirm' => __('admin.messages.deleting_feed')]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
