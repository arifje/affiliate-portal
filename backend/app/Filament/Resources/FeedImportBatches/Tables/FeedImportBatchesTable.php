<?php

namespace App\Filament\Resources\FeedImportBatches\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FeedImportBatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('feed.name')
                    ->label(__('admin.fields.feed'))
                    ->searchable(),
                TextColumn::make('mappingProfile.name')
                    ->label(__('admin.fields.mapping_profile'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('admin.fields.status'))
                    ->badge()
                    ->searchable(),
                TextColumn::make('total_rows')
                    ->label(__('admin.fields.total_rows'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('processed_rows')
                    ->label(__('admin.fields.processed_rows'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_rows')
                    ->label(__('admin.fields.created_rows'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('updated_rows')
                    ->label(__('admin.fields.updated_rows'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('skipped_rows')
                    ->label(__('admin.fields.skipped_rows'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('failed_rows')
                    ->label(__('admin.fields.failed_rows'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('started_at')
                    ->label(__('admin.fields.last_import_started_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('finished_at')
                    ->label(__('admin.fields.last_import_finished_at'))
                    ->dateTime()
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
                SelectFilter::make('status')
                    ->label(__('admin.fields.status'))
                    ->options(__('admin.options.import_statuses')),
                SelectFilter::make('feed_id')
                    ->label(__('admin.fields.feed'))
                    ->relationship('feed', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('started_at', 'desc')
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
