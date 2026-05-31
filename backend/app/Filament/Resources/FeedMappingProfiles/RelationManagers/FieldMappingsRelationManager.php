<?php

namespace App\Filament\Resources\FeedMappingProfiles\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FieldMappingsRelationManager extends RelationManager
{
    protected static string $relationship = 'fieldMappings';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.mapping'))
                    ->schema([
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('source_field')
            ->columns([
                TextColumn::make('canonicalField.key')
                    ->label(__('admin.fields.canonical_field'))
                    ->searchable(),
                TextColumn::make('canonicalField.label')
                    ->label(__('admin.fields.label'))
                    ->toggleable(),
                TextColumn::make('source_field')
                    ->label(__('admin.fields.source_field'))
                    ->searchable(),
                TextColumn::make('source_path')
                    ->label(__('admin.fields.source_path'))
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('transform_type')
                    ->label(__('admin.fields.transform_type'))
                    ->badge()
                    ->searchable(),
                IconColumn::make('is_required')
                    ->label(__('admin.fields.is_required'))
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label(__('admin.fields.sort_order'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('admin.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
    }
}
