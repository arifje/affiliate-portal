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
                    ->label(__('admin.fields.name'))
                    ->searchable(),
                TextColumn::make('slug')
                    ->label(__('admin.fields.slug'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('provider')
                    ->label(__('admin.fields.provider'))
                    ->badge()
                    ->searchable(),
                TextColumn::make('source_format')
                    ->label(__('admin.fields.source_format'))
                    ->badge()
                    ->searchable(),
                TextColumn::make('site.name')
                    ->label(__('admin.fields.site'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('partner.name')
                    ->label(__('admin.fields.partner'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('currency')
                    ->label(__('admin.fields.currency'))
                    ->searchable(),
                IconColumn::make('is_template')
                    ->label(__('admin.fields.is_template'))
                    ->boolean(),
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
                SelectFilter::make('provider')
                    ->label(__('admin.fields.provider'))
                    ->options(__('admin.options.providers')),
                SelectFilter::make('source_format')
                    ->label(__('admin.fields.source_format'))
                    ->options(__('admin.options.source_formats')),
                TernaryFilter::make('is_template')
                    ->label(__('admin.fields.is_template')),
                TernaryFilter::make('is_active')
                    ->label(__('admin.fields.is_active')),
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
