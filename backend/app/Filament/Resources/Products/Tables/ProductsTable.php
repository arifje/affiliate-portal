<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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
                    ->label('Image')
                    ->square(),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                TextColumn::make('site.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('partner.name')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('feed.name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('brand')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('price')
                    ->money(fn (Product $record): string => $record->currency)
                    ->sortable(),
                TextColumn::make('availability')
                    ->badge()
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('merchant_category')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('product_type')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Draft'),
                TextColumn::make('imported_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('site_id')
                    ->label('Site')
                    ->relationship('site', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('partner_id')
                    ->label('Partner')
                    ->relationship('partner', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('feed_id')
                    ->label('Feed')
                    ->relationship('feed', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('brand')
                    ->options(fn (): array => Product::query()
                        ->whereNotNull('brand')
                        ->where('brand', '!=', '')
                        ->distinct()
                        ->orderBy('brand')
                        ->pluck('brand', 'brand')
                        ->all())
                    ->searchable(),
                SelectFilter::make('availability')
                    ->options([
                        'in_stock' => 'In stock',
                        'out_of_stock' => 'Out of stock',
                        'preorder' => 'Preorder',
                        'backorder' => 'Backorder',
                    ]),
                TernaryFilter::make('is_active'),
                TernaryFilter::make('published_at')
                    ->label('Published')
                    ->nullable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
