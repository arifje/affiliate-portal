<?php

namespace App\Filament\Resources\Feeds\Schemas;

use App\Models\CanonicalField;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class FeedForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.feed'))
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
                            ->required()
                            ->helperText(__('admin.helpers.partner_provider')),
                        TextInput::make('name')
                            ->label(__('admin.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label(__('admin.fields.slug'))
                            ->required()
                            ->maxLength(255),
                        Toggle::make('is_active')
                            ->label(__('admin.fields.is_active'))
                            ->required()
                            ->default(true),
                    ])
                    ->columns(2),
                Section::make(__('admin.sections.source'))
                    ->schema([
                        Select::make('source_type')
                            ->label(__('admin.fields.source_type'))
                            ->options(__('admin.options.source_types'))
                            ->live()
                            ->required()
                            ->default('url'),
                        Select::make('source_format')
                            ->label(__('admin.fields.source_format'))
                            ->options(__('admin.options.source_formats'))
                            ->live()
                            ->required()
                            ->default('csv'),
                        TextInput::make('source_encoding')
                            ->label(__('admin.fields.source_encoding'))
                            ->required()
                            ->default('utf-8')
                            ->maxLength(255),
                        Textarea::make('source_url')
                            ->label(__('admin.fields.source_url'))
                            ->visible(fn (Get $get): bool => $get('source_type') !== 'file')
                            ->required(fn (Get $get): bool => $get('source_type') !== 'file')
                            ->rows(3)
                            ->columnSpanFull(),
                        FileUpload::make('source_file_path')
                            ->label(__('admin.fields.source_file'))
                            ->visible(fn (Get $get): bool => $get('source_type') === 'file')
                            ->required(fn (Get $get): bool => $get('source_type') === 'file')
                            ->helperText(__('admin.helpers.source_file'))
                            ->acceptedFileTypes([
                                'text/csv',
                                'text/plain',
                                'application/csv',
                                'application/json',
                                'application/xml',
                                'text/xml',
                                'application/vnd.ms-excel',
                            ])
                            ->disk('local')
                            ->directory(fn (Get $get): string => 'feeds/site-'.($get('site_id') ?: 'unassigned'))
                            ->visibility('private')
                            ->storeFileNamesIn('source_file_original_name')
                            ->maxSize(131072)
                            ->columnSpanFull(),
                        Select::make('delimiter')
                            ->label(__('admin.fields.delimiter'))
                            ->options(__('admin.options.csv_delimiters'))
                            ->default(',')
                            ->visible(fn (Get $get): bool => $get('source_format') === 'csv')
                            ->native(false),
                        TextInput::make('enclosure')
                            ->label(__('admin.fields.enclosure'))
                            ->visible(fn (Get $get): bool => $get('source_format') === 'csv')
                            ->maxLength(8),
                        TextInput::make('decimal_separator')
                            ->label(__('admin.fields.decimal_separator'))
                            ->required()
                            ->default('.')
                            ->maxLength(4),
                        TextInput::make('thousands_separator')
                            ->label(__('admin.fields.thousands_separator'))
                            ->maxLength(4),
                        Toggle::make('first_row_is_header')
                            ->label(__('admin.fields.first_row_is_header'))
                            ->visible(fn (Get $get): bool => $get('source_format') === 'csv')
                            ->default(true),
                        TextInput::make('row_selector')
                            ->label(__('admin.fields.primary_element'))
                            ->helperText(__('admin.helpers.primary_element'))
                            ->maxLength(255),
                        KeyValue::make('credentials')
                            ->label(__('admin.fields.credentials'))
                            ->keyLabel(__('admin.fields.key'))
                            ->valueLabel(__('admin.fields.default_value'))
                            ->columnSpanFull(),
                        KeyValue::make('request_headers')
                            ->label(__('admin.fields.request_headers'))
                            ->keyLabel(__('admin.fields.header'))
                            ->valueLabel(__('admin.fields.value'))
                            ->helperText(__('admin.helpers.request_headers'))
                            ->columnSpanFull(),
                        KeyValue::make('request_query_params')
                            ->label(__('admin.fields.request_query_params'))
                            ->keyLabel(__('admin.fields.query_param'))
                            ->valueLabel(__('admin.fields.value'))
                            ->helperText(__('admin.helpers.request_query_params'))
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
                Section::make(__('admin.sections.mapping_and_schedule'))
                    ->schema([
                        Select::make('unique_identifier_field')
                            ->label(__('admin.fields.unique_identifier_field'))
                            ->options(fn (): array => CanonicalField::query()
                                ->active()
                                ->orderBy('field_group')
                                ->orderBy('sort_order')
                                ->orderBy('label')
                                ->pluck('label', 'key')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->placeholder('provider_product_id')
                            ->helperText(__('admin.helpers.unique_identifier_field')),
                        TextInput::make('schedule')
                            ->label(__('admin.fields.schedule'))
                            ->placeholder(__('admin.placeholders.schedule'))
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Section::make(__('admin.sections.import_strategy'))
                    ->description(__('admin.helpers.import_strategy'))
                    ->schema([
                        Checkbox::make('import_create_new')
                            ->label(__('admin.fields.import_create_new'))
                            ->helperText(__('admin.helpers.import_create_new'))
                            ->default(true),
                        Checkbox::make('import_update_existing')
                            ->label(__('admin.fields.import_update_existing'))
                            ->helperText(__('admin.helpers.import_update_existing'))
                            ->default(true),
                        Checkbox::make('import_disable_missing_globally')
                            ->label(__('admin.fields.import_disable_missing_globally'))
                            ->helperText(__('admin.helpers.import_disable_missing_globally')),
                        Checkbox::make('import_disable_missing_for_site')
                            ->label(__('admin.fields.import_disable_missing_for_site'))
                            ->helperText(__('admin.helpers.import_disable_missing_for_site')),
                        Checkbox::make('import_delete_missing')
                            ->label(__('admin.fields.import_delete_missing'))
                            ->helperText(__('admin.helpers.import_delete_missing')),
                        Checkbox::make('import_update_search_indexes')
                            ->label(__('admin.fields.import_update_search_indexes'))
                            ->helperText(__('admin.helpers.import_update_search_indexes'))
                            ->default(true),
                        Textarea::make('import_strategy_notes')
                            ->label(__('admin.fields.import_strategy_notes'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
