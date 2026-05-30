<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ownership')
                    ->schema([
                        Select::make('site_id')
                            ->relationship('site', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('partner_id')
                            ->relationship('partner', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('feed_id')
                            ->relationship('feed', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('category_id')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),
                Section::make('Identity')
                    ->schema([
                        TextInput::make('provider_product_id')
                            ->maxLength(255),
                        TextInput::make('sku')
                            ->label('SKU')
                            ->maxLength(255),
                        TextInput::make('ean')
                            ->label('EAN/GTIN')
                            ->maxLength(255),
                        TextInput::make('mpn')
                            ->label('MPN')
                            ->maxLength(255),
                        TextInput::make('brand')
                            ->maxLength(255),
                        TextInput::make('item_group_id')
                            ->maxLength(255),
                    ])
                    ->columns(3),
                Section::make('Content')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->rows(5)
                            ->columnSpanFull(),
                        TextInput::make('merchant_category')
                            ->maxLength(255),
                        TextInput::make('product_type')
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Section::make('Media and URLs')
                    ->schema([
                        Textarea::make('image_url')
                            ->rows(2)
                            ->columnSpanFull(),
                        TagsInput::make('additional_image_urls')
                            ->columnSpanFull(),
                        Textarea::make('product_url')
                            ->rows(2)
                            ->columnSpanFull(),
                        Textarea::make('affiliate_url')
                            ->required()
                            ->rows(2)
                            ->columnSpanFull(),
                        Textarea::make('tracking_url')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
                Section::make('Pricing and availability')
                    ->schema([
                        TextInput::make('price')
                            ->numeric(),
                        TextInput::make('old_price')
                            ->numeric(),
                        TextInput::make('shipping_cost')
                            ->numeric(),
                        TextInput::make('currency')
                            ->required()
                            ->default('EUR')
                            ->maxLength(3),
                        Select::make('availability')
                            ->options([
                                'in_stock' => 'In stock',
                                'out_of_stock' => 'Out of stock',
                                'preorder' => 'Preorder',
                                'backorder' => 'Backorder',
                            ]),
                        TextInput::make('stock_quantity')
                            ->numeric(),
                        TextInput::make('delivery_time')
                            ->maxLength(255),
                        Select::make('condition')
                            ->options([
                                'new' => 'New',
                                'used' => 'Used',
                                'refurbished' => 'Refurbished',
                            ])
                            ->required()
                            ->default('new'),
                    ])
                    ->columns(4),
                Section::make('Variants')
                    ->schema([
                        TextInput::make('color')
                            ->maxLength(255),
                        TextInput::make('size')
                            ->maxLength(255),
                        TextInput::make('gender')
                            ->maxLength(255),
                        TextInput::make('material')
                            ->maxLength(255),
                        TextInput::make('pattern')
                            ->maxLength(255),
                        TextInput::make('age_group')
                            ->maxLength(255),
                    ])
                    ->columns(3),
                Section::make('Publishing')
                    ->schema([
                        Toggle::make('is_active')
                            ->required()
                            ->default(true),
                        DateTimePicker::make('published_at'),
                        DateTimePicker::make('imported_at'),
                    ])
                    ->columns(3),
                Section::make('Metadata')
                    ->schema([
                        KeyValue::make('metadata')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
