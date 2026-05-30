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
                    ->searchable()
                    ->sortable(),
                TextColumn::make('site.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('partner.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('provider')
                    ->badge()
                    ->searchable(),
                TextColumn::make('source_type')
                    ->badge()
                    ->searchable(),
                TextColumn::make('mappingProfile.name')
                    ->label('Mapping profile')
                    ->searchable()
                    ->toggleable(),
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
                TextColumn::make('last_import_finished_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('site_id')
                    ->relationship('site', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('partner_id')
                    ->relationship('partner', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('provider')
                    ->options([
                        'awin' => 'Awin',
                        'daisycon' => 'Daisycon',
                        'tradetracker' => 'TradeTracker',
                        'custom' => 'Custom',
                    ]),
                TernaryFilter::make('is_active'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription('Deleting a feed keeps imported products but removes the feed link from them.'),
            ]);
    }
}
