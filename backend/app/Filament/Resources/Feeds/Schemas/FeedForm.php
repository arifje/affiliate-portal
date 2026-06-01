<?php

namespace App\Filament\Resources\Feeds\Schemas;

use App\Models\CanonicalField;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
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
                            ->required(),
                        TextInput::make('name')
                            ->label(__('admin.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label(__('admin.fields.slug'))
                            ->required()
                            ->maxLength(255),
                        Select::make('provider')
                            ->label(__('admin.fields.provider'))
                            ->options(__('admin.options.providers'))
                            ->required(),
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
                            ->required()
                            ->default('url'),
                        Textarea::make('source_url')
                            ->label(__('admin.fields.source_url'))
                            ->rows(3)
                            ->columnSpanFull(),
                        KeyValue::make('credentials')
                            ->label(__('admin.fields.credentials'))
                            ->keyLabel(__('admin.fields.key'))
                            ->valueLabel(__('admin.fields.default_value'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make(__('admin.sections.mapping_and_schedule'))
                    ->schema([
                        Select::make('mapping_profile_id')
                            ->label(__('admin.fields.mapping_profile'))
                            ->relationship('mappingProfile', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText(__('admin.helpers.mapping_profile')),
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
                        KeyValue::make('mapping')
                            ->label(__('admin.fields.mapping'))
                            ->keyLabel(__('admin.fields.key'))
                            ->valueLabel(__('admin.fields.default_value'))
                            ->columnSpanFull(),
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
