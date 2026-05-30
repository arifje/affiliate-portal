<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
use Filament\Infolists\Components\CodeEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Product')
                    ->schema([
                        ImageEntry::make('image_url')
                            ->label('Image')
                            ->placeholder('-'),
                        TextEntry::make('title')
                            ->columnSpanFull(),
                        TextEntry::make('site.name')
                            ->label('Site'),
                        TextEntry::make('partner.name')
                            ->label('Partner'),
                        TextEntry::make('feed.name')
                            ->label('Feed')
                            ->placeholder('-'),
                        TextEntry::make('category.name')
                            ->label('Category')
                            ->placeholder('-'),
                        TextEntry::make('brand')
                            ->placeholder('-'),
                        TextEntry::make('slug'),
                    ])
                    ->columns(2),
                Section::make('Identifiers')
                    ->schema([
                        TextEntry::make('provider_product_id')
                            ->placeholder('-'),
                        TextEntry::make('sku')
                            ->label('SKU')
                            ->placeholder('-'),
                        TextEntry::make('ean')
                            ->label('EAN/GTIN')
                            ->placeholder('-'),
                        TextEntry::make('mpn')
                            ->label('MPN')
                            ->placeholder('-'),
                        TextEntry::make('item_group_id')
                            ->placeholder('-'),
                    ])
                    ->columns(3),
                Section::make('Content')
                    ->schema([
                        TextEntry::make('description')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('merchant_category')
                            ->placeholder('-'),
                        TextEntry::make('product_type')
                            ->placeholder('-'),
                    ])
                    ->columns(2),
                Section::make('URLs')
                    ->schema([
                        TextEntry::make('image_url')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('additional_image_urls')
                            ->listWithLineBreaks()
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('product_url')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('affiliate_url')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('tracking_url')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
                Section::make('Pricing and availability')
                    ->schema([
                        TextEntry::make('price')
                            ->money(fn (Product $record): string => $record->currency)
                            ->placeholder('-'),
                        TextEntry::make('old_price')
                            ->money(fn (Product $record): string => $record->currency)
                            ->placeholder('-'),
                        TextEntry::make('shipping_cost')
                            ->money(fn (Product $record): string => $record->currency)
                            ->placeholder('-'),
                        TextEntry::make('currency'),
                        TextEntry::make('availability')
                            ->badge()
                            ->placeholder('-'),
                        TextEntry::make('stock_quantity')
                            ->numeric()
                            ->placeholder('-'),
                        TextEntry::make('delivery_time')
                            ->placeholder('-'),
                        TextEntry::make('condition'),
                    ])
                    ->columns(4),
                Section::make('Variants')
                    ->schema([
                        TextEntry::make('color')
                            ->placeholder('-'),
                        TextEntry::make('size')
                            ->placeholder('-'),
                        TextEntry::make('gender')
                            ->placeholder('-'),
                        TextEntry::make('material')
                            ->placeholder('-'),
                        TextEntry::make('pattern')
                            ->placeholder('-'),
                        TextEntry::make('age_group')
                            ->placeholder('-'),
                    ])
                    ->columns(3),
                Section::make('Publishing')
                    ->schema([
                        IconEntry::make('is_active')
                            ->boolean(),
                        TextEntry::make('published_at')
                            ->dateTime()
                            ->placeholder('Draft'),
                        TextEntry::make('imported_at')
                            ->dateTime()
                            ->placeholder('-'),
                    ])
                    ->columns(3),
                Section::make('Metadata')
                    ->schema([
                        CodeEntry::make('metadata')
                            ->grammar('json')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        CodeEntry::make('raw_payload')
                            ->grammar('json')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
