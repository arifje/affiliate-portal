<?php

namespace App\Filament\Resources\Sites\Tables;

use App\Filament\Resources\Sites\SiteResource;
use App\Models\Site;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SitesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount([
                'categories',
                'feeds',
                'products',
            ]))
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('primary_domain')
                    ->label(__('admin.fields.primary_domain'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label(__('admin.fields.slug'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('locale')
                    ->label(__('admin.fields.locale'))
                    ->badge()
                    ->searchable(),
                TextColumn::make('currency')
                    ->label(__('admin.fields.currency'))
                    ->badge()
                    ->searchable(),
                TextColumn::make('categories_count')
                    ->label(__('admin.fields.categories'))
                    ->numeric()
                    ->sortable(),
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
                TernaryFilter::make('is_active')
                    ->label(__('admin.fields.is_active')),
            ])
            ->defaultSort('name')
            ->recordUrl(fn (Site $record): string => SiteResource::getUrl('edit', ['record' => $record]))
            ->recordActions([
                Action::make('preview')
                    ->label(__('admin.actions.preview'))
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (Site $record): string => SiteResource::getPreviewUrl($record))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription(__('admin.messages.deleting_site')),
            ]);
    }
}
