<?php

namespace App\Filament\Resources\CanonicalFields\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CanonicalFieldsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->label(__('admin.fields.key'))
                    ->searchable(),
                TextColumn::make('field_group')
                    ->label(__('admin.fields.field_group'))
                    ->searchable(),
                TextColumn::make('label')
                    ->label(__('admin.fields.label'))
                    ->searchable(),
                TextColumn::make('data_type')
                    ->label(__('admin.fields.data_type'))
                    ->badge()
                    ->searchable(),
                TextColumn::make('target_column')
                    ->label(__('admin.fields.target_column'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('metadata_path')
                    ->label(__('admin.fields.metadata_path'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_required')
                    ->label(__('admin.fields.is_required'))
                    ->boolean(),
                IconColumn::make('is_searchable')
                    ->label(__('admin.fields.is_searchable'))
                    ->boolean(),
                IconColumn::make('is_filterable')
                    ->label(__('admin.fields.is_filterable'))
                    ->boolean(),
                IconColumn::make('is_variant')
                    ->label(__('admin.fields.is_variant'))
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label(__('admin.fields.sort_order'))
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('admin.fields.is_active'))
                    ->boolean(),
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
                SelectFilter::make('field_group')
                    ->label(__('admin.fields.field_group'))
                    ->options(__('admin.options.field_groups')),
                TernaryFilter::make('is_required')
                    ->label(__('admin.fields.is_required')),
                TernaryFilter::make('is_filterable')
                    ->label(__('admin.fields.is_filterable')),
                TernaryFilter::make('is_active')
                    ->label(__('admin.fields.is_active')),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
