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
                    ->searchable()
                    ->sortable(),
                TextColumn::make('canonicalField.key')
                    ->label('Canonical field')
                    ->searchable(),
                TextColumn::make('source_field')
                    ->searchable(),
                TextColumn::make('source_path')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('transform_type')
                    ->badge()
                    ->searchable(),
                IconColumn::make('is_required')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
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
                SelectFilter::make('feed_mapping_profile_id')
                    ->relationship('profile', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('transform_type')
                    ->options([
                        'copy' => 'Copy',
                        'money' => 'Money',
                        'integer' => 'Integer',
                        'boolean' => 'Boolean',
                        'availability' => 'Availability',
                        'array' => 'Array',
                    ]),
                TernaryFilter::make('is_required'),
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
