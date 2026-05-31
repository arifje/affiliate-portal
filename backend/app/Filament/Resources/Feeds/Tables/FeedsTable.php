<?php

namespace App\Filament\Resources\Feeds\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FeedsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount([
                'products',
                'importBatches',
            ]))
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('site.name')
                    ->label(__('admin.fields.site'))
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
                TextColumn::make('source_type')
                    ->label(__('admin.fields.source_type'))
                    ->badge()
                    ->searchable(),
                TextColumn::make('mappingProfile.name')
                    ->label(__('admin.fields.mapping_profile'))
                    ->searchable()
                    ->toggleable(),
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
                TextColumn::make('last_import_finished_at')
                    ->label(__('admin.fields.last_import_finished_at'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),
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
                SelectFilter::make('site_id')
                    ->label(__('admin.fields.site'))
                    ->relationship('site', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('partner_id')
                    ->label(__('admin.fields.partner'))
                    ->relationship('partner', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('provider')
                    ->label(__('admin.fields.provider'))
                    ->options(__('admin.options.providers')),
                TernaryFilter::make('is_active')
                    ->label(__('admin.fields.is_active')),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription(__('admin.messages.deleting_feed')),
            ]);
    }
}
