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
                    ->label(__('admin.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('provider')
                    ->label(__('admin.fields.provider'))
                    ->badge()
                    ->searchable(),
                TextColumn::make('website_url')
                    ->label(__('admin.fields.website_url'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('feeds_count')
                    ->label(__('admin.fields.feeds'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('products_count')
                    ->label(__('admin.fields.products'))
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
                SelectFilter::make('provider')
                    ->label(__('admin.fields.provider'))
                    ->options(__('admin.options.providers')),
                TernaryFilter::make('is_active')
                    ->label(__('admin.fields.is_active')),
            ])
            ->defaultSort('name')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription(__('admin.messages.deleting_partner')),
            ]);
    }
}
