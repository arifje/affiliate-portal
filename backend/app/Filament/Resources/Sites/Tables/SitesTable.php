<?php

namespace App\Filament\Resources\Sites\Tables;

use App\Filament\Resources\Sites\SiteResource;
use App\Models\Site;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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
                    ->searchable()
                    ->sortable(),
                TextColumn::make('primary_domain')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('locale')
                    ->badge()
                    ->searchable(),
                TextColumn::make('currency')
                    ->badge()
                    ->searchable(),
                TextColumn::make('categories_count')
                    ->label('Categories')
                    ->numeric()
                    ->sortable(),
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
                TernaryFilter::make('is_active'),
            ])
            ->defaultSort('name')
            ->recordActions([
                Action::make('preview')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (Site $record): string => SiteResource::getPreviewUrl($record))
                    ->openUrlInNewTab(),
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription('Deleting a site also removes its categories, feeds, products, and clicks.'),
            ]);
    }
}
