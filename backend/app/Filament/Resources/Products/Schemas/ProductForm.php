<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.owner'))
                    ->schema([
                        Select::make('site_id')
                            ->label(__('admin.fields.site'))
                            ->relationship('site', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('partner_id')
                            ->label(__('admin.fields.partner'))
                            ->relationship('partner', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('feed_id')
                            ->label(__('admin.fields.feed'))
                            ->relationship('feed', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('category_id')
                            ->label(__('admin.fields.category'))
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),
                Section::make(__('admin.sections.identity'))
                    ->schema([
                        TextInput::make('provider_product_id')
                            ->label(__('admin.fields.provider_product_id'))
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
                            ->label(__('admin.fields.brand'))
                            ->maxLength(255),
                        TextInput::make('item_group_id')
                            ->label(__('admin.fields.item_group_id'))
                            ->maxLength(255),
                    ])
                    ->columns(3),
                Section::make(__('admin.sections.content'))
                    ->schema([
                        TextInput::make('title')
                            ->label(__('admin.fields.title'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label(__('admin.fields.slug'))
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label(__('admin.fields.description'))
                            ->rows(5)
                            ->columnSpanFull(),
                        TextInput::make('merchant_category')
                            ->label(__('admin.fields.category'))
                            ->maxLength(255),
                        TextInput::make('product_type')
                            ->label(__('admin.fields.product_type'))
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Section::make(__('admin.sections.media_urls'))
                    ->schema([
                        Textarea::make('image_url')
                            ->label(__('admin.fields.image_url'))
                            ->rows(2)
                            ->columnSpanFull(),
                        TagsInput::make('additional_image_urls')
                            ->label(__('admin.fields.additional_image_urls'))
                            ->columnSpanFull(),
                        Textarea::make('product_url')
                            ->label(__('admin.fields.product_url'))
                            ->rows(2)
                            ->columnSpanFull(),
                        Textarea::make('affiliate_url')
                            ->label(__('admin.fields.affiliate_url'))
                            ->required()
                            ->rows(2)
                            ->columnSpanFull(),
                        Textarea::make('tracking_url')
                            ->label(__('admin.fields.tracking_url'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
                Section::make(__('admin.sections.pricing_availability'))
                    ->schema([
                        TextInput::make('price')
                            ->label(__('admin.fields.price'))
                            ->numeric(),
                        TextInput::make('old_price')
                            ->label(__('admin.fields.old_price'))
                            ->numeric(),
                        TextInput::make('shipping_cost')
                            ->label(__('admin.fields.shipping_cost'))
                            ->numeric(),
                        TextInput::make('currency')
                            ->label(__('admin.fields.currency'))
                            ->required()
                            ->default('EUR')
                            ->maxLength(3),
                        Select::make('availability')
                            ->label(__('admin.fields.availability'))
                            ->options(__('admin.options.availability')),
                        TextInput::make('stock_quantity')
                            ->label(__('admin.fields.stock_quantity'))
                            ->numeric(),
                        TextInput::make('delivery_time')
                            ->label(__('admin.fields.delivery_time'))
                            ->maxLength(255),
                        Select::make('condition')
                            ->label(__('admin.fields.condition'))
                            ->options(__('admin.options.conditions'))
                            ->required()
                            ->default('new'),
                    ])
                    ->columns(4),
                Section::make(__('admin.sections.variants'))
                    ->schema([
                        TextInput::make('color')
                            ->label(__('admin.fields.color'))
                            ->maxLength(255),
                        TextInput::make('size')
                            ->label(__('admin.fields.size'))
                            ->maxLength(255),
                        TextInput::make('gender')
                            ->label(__('admin.fields.gender'))
                            ->maxLength(255),
                        TextInput::make('material')
                            ->label(__('admin.fields.material'))
                            ->maxLength(255),
                        TextInput::make('pattern')
                            ->label(__('admin.fields.pattern'))
                            ->maxLength(255),
                        TextInput::make('age_group')
                            ->label(__('admin.fields.age_group'))
                            ->maxLength(255),
                    ])
                    ->columns(3),
                Section::make(__('admin.sections.publishing'))
                    ->schema([
                        Toggle::make('is_active')
                            ->label(__('admin.fields.is_active'))
                            ->required()
                            ->default(true),
                        DateTimePicker::make('published_at')
                            ->label(__('admin.fields.published_at')),
                        DateTimePicker::make('imported_at')
                            ->label(__('admin.fields.imported_at')),
                    ])
                    ->columns(3),
                Section::make(__('admin.sections.merchandising'))
                    ->schema([
                        Toggle::make('is_featured')
                            ->label(__('admin.fields.is_featured'))
                            ->helperText(__('admin.helpers.featured_product')),
                        TextInput::make('featured_sort_order')
                            ->label(__('admin.fields.sort_order'))
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->helperText(__('admin.helpers.featured_sort_order')),
                    ])
                    ->columns(2),
                Section::make(__('admin.sections.metadata'))
                    ->schema([
                        KeyValue::make('metadata')
                            ->label(__('admin.fields.metadata'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
