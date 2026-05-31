<?php

namespace App\Filament\Resources\FeedFieldMappings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class FeedFieldMappingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('profile.name')
                    ->label(__('admin.fields.mapping_profile'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('canonicalField.key')
                    ->label(__('admin.fields.canonical_field'))
                    ->searchable(),
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
                SelectFilter::make('feed_mapping_profile_id')
                    ->label(__('admin.fields.mapping_profile'))
                    ->relationship('profile', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('transform_type')
                    ->label(__('admin.fields.transform_type'))
                    ->options(__('admin.options.transform_types')),
                TernaryFilter::make('is_required')
                    ->label(__('admin.fields.is_required')),
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
