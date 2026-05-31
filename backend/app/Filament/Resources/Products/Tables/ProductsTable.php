<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_url')
                    ->label(__('admin.fields.image'))
                    ->square(),
                TextColumn::make('title')
                    ->label(__('admin.fields.title'))
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                TextColumn::make('site.name')
                    ->label(__('admin.fields.site'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('partner.name')
                    ->label(__('admin.fields.partner'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('feed.name')
                    ->label(__('admin.fields.feed'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('brand')
                    ->label(__('admin.fields.brand'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('price')
                    ->label(__('admin.fields.price'))
                    ->money(fn (Product $record): string => $record->currency)
                    ->sortable(),
                TextColumn::make('availability')
                    ->label(__('admin.fields.availability'))
                    ->badge()
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('merchant_category')
                    ->label(__('admin.fields.category'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('product_type')
                    ->label(__('admin.fields.product_type'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_featured')
                    ->label(__('admin.fields.is_featured'))
                    ->boolean()
                    ->sortable(),
                TextColumn::make('featured_sort_order')
                    ->label(__('admin.fields.sort_order'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->label(__('admin.fields.is_active'))
                    ->boolean(),
                TextColumn::make('published_at')
                    ->label(__('admin.fields.published_at'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder(__('admin.placeholders.draft')),
                TextColumn::make('imported_at')
                    ->label(__('admin.fields.imported_at'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('admin.fields.created_at'))
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
                SelectFilter::make('feed_id')
                    ->label(__('admin.fields.feed'))
                    ->relationship('feed', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('category_id')
                    ->label(__('admin.fields.category'))
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('brand')
                    ->label(__('admin.fields.brand'))
                    ->options(fn (): array => Product::query()
                        ->whereNotNull('brand')
                        ->where('brand', '!=', '')
                        ->distinct()
                        ->orderBy('brand')
                        ->pluck('brand', 'brand')
                        ->all())
                    ->searchable(),
                SelectFilter::make('availability')
                    ->label(__('admin.fields.availability'))
                    ->options(__('admin.options.availability')),
                TernaryFilter::make('is_active')
                    ->label(__('admin.fields.is_active')),
                TernaryFilter::make('is_featured')
                    ->label(__('admin.fields.is_featured')),
                TernaryFilter::make('published_at')
                    ->label(__('admin.fields.published'))
                    ->nullable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordUrl(fn (Product $record): string => ProductResource::getUrl('edit', ['record' => $record]))
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
