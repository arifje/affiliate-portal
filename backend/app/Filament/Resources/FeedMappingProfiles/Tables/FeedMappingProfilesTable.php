<?php

namespace App\Filament\Resources\FeedMappingProfiles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class FeedMappingProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('provider')
                    ->badge()
                    ->searchable(),
                TextColumn::make('source_format')
                    ->badge()
                    ->searchable(),
                TextColumn::make('site.name')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('partner.name')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('currency')
                    ->searchable(),
                IconColumn::make('is_template')
                    ->boolean(),
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
                SelectFilter::make('source_format')
                    ->options([
                        'csv' => 'CSV',
                        'json' => 'JSON',
                        'jsonl' => 'JSONL',
                        'xml' => 'XML',
                    ]),
                TernaryFilter::make('is_template'),
                TernaryFilter::make('is_active'),
            ])
            ->defaultSort('provider')
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
