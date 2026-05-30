<?php

namespace App\Filament\Resources\Partners\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PartnersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount([
                'feeds',
                'products',
            ]))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('provider')
                    ->badge()
                    ->searchable(),
                TextColumn::make('website_url')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('feeds_count')
                    ->label('Feeds')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('products_count')
                    ->label('Products')
                    ->numeric()
                    ->sortable(),
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
                SelectFilter::make('provider')
                    ->options([
                        'awin' => 'Awin',
                        'daisycon' => 'Daisycon',
                        'tradetracker' => 'TradeTracker',
                        'custom' => 'Custom',
                    ]),
                TernaryFilter::make('is_active'),
            ])
            ->defaultSort('name')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription('Deleting a partner also removes its feeds and products.'),
            ]);
    }
}
