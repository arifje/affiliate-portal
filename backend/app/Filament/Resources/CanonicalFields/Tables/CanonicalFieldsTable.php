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
                    ->searchable(),
                TextColumn::make('field_group')
                    ->searchable(),
                TextColumn::make('label')
                    ->searchable(),
                TextColumn::make('data_type')
                    ->badge()
                    ->searchable(),
                TextColumn::make('target_column')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('metadata_path')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_required')
                    ->boolean(),
                IconColumn::make('is_searchable')
                    ->boolean(),
                IconColumn::make('is_filterable')
                    ->boolean(),
                IconColumn::make('is_variant')
                    ->boolean(),
                TextColumn::make('sort_order')
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
                SelectFilter::make('field_group')
                    ->options([
                        'identity' => 'Identity',
                        'content' => 'Content',
                        'urls' => 'URLs',
                        'pricing' => 'Pricing',
                        'availability' => 'Availability',
                        'variants' => 'Variants',
                        'classification' => 'Classification',
                        'compliance' => 'Compliance',
                    ]),
                TernaryFilter::make('is_required'),
                TernaryFilter::make('is_filterable'),
                TernaryFilter::make('is_active'),
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
