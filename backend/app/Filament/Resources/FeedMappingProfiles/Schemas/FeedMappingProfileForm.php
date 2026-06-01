<?php

namespace App\Filament\Resources\FeedMappingProfiles\Schemas;

use App\Models\FeedMappingProfile;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FeedMappingProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.profile'))
                    ->schema([
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
                        Select::make('source_format')
                            ->label(__('admin.fields.source_format'))
                            ->options(__('admin.options.source_formats'))
                            ->required()
                            ->default('csv'),
                        Select::make('site_id')
                            ->label(__('admin.fields.site'))
                            ->relationship('site', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('partner_id')
                            ->label(__('admin.fields.partner'))
                            ->relationship('partner', 'name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),
                Section::make(__('admin.sections.parsing'))
                    ->schema([
                        TextInput::make('source_encoding')
                            ->label(__('admin.fields.source_encoding'))
                            ->required()
                            ->default('utf-8')
                            ->maxLength(255),
                        TextInput::make('delimiter')
                            ->label(__('admin.fields.delimiter'))
                            ->maxLength(8),
                        TextInput::make('enclosure')
                            ->label(__('admin.fields.enclosure'))
                            ->maxLength(8),
                        TextInput::make('decimal_separator')
                            ->label(__('admin.fields.decimal_separator'))
                            ->required()
                            ->default('.')
                            ->maxLength(4),
                        TextInput::make('thousands_separator')
                            ->label(__('admin.fields.thousands_separator'))
                            ->maxLength(4),
                        Select::make('row_selector')
                            ->label(__('admin.fields.primary_element'))
                            ->options(fn (?FeedMappingProfile $record): array => collect($record?->available_elements ?? [])
                                ->mapWithKeys(fn (array $element): array => [
                                    $element['path'] => $element['label'] ?? $element['path'],
                                ])
                                ->all())
                            ->searchable()
                            ->placeholder(__('admin.placeholders.analyze_feed_first'))
                            ->helperText(__('admin.helpers.primary_element')),
                        Toggle::make('first_row_is_header')
                            ->label(__('admin.fields.first_row_is_header'))
                            ->required()
                            ->default(true),
                    ])
                    ->columns(3),
                Section::make(__('admin.sections.feed_discovery'))
                    ->description(__('admin.helpers.feed_discovery'))
                    ->schema([
                        Textarea::make('available_elements_preview')
                            ->label(__('admin.fields.available_elements'))
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(5)
                            ->formatStateUsing(fn (?FeedMappingProfile $record): string => collect($record?->available_elements ?? [])
                                ->map(fn (array $element): string => ($element['label'] ?? $element['path'] ?? '-'))
                                ->implode(PHP_EOL)),
                        Textarea::make('sample_fields_preview')
                            ->label(__('admin.fields.sample_fields'))
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(8)
                            ->formatStateUsing(fn (?FeedMappingProfile $record): string => collect($record?->sample_fields ?? [])
                                ->map(fn (array $field): string => ($field['path'] ?? '-').': '.($field['sample'] ?? ''))
                                ->implode(PHP_EOL)),
                        Textarea::make('sample_payload_preview')
                            ->label(__('admin.fields.sample_payload'))
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(8)
                            ->formatStateUsing(fn (?FeedMappingProfile $record): string => json_encode($record?->sample_payload ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: ''),
                    ])
                    ->columns(3)
                    ->collapsible(),
                Section::make(__('admin.sections.defaults'))
                    ->schema([
                        TextInput::make('currency')
                            ->label(__('admin.fields.currency'))
                            ->required()
                            ->default('EUR')
                            ->maxLength(3),
                        TextInput::make('locale')
                            ->label(__('admin.fields.locale'))
                            ->required()
                            ->default('nl_NL')
                            ->maxLength(12),
                        TextInput::make('timezone')
                            ->label(__('admin.fields.timezone'))
                            ->required()
                            ->default('Europe/Amsterdam')
                            ->maxLength(255),
                        Toggle::make('is_template')
                            ->label(__('admin.fields.is_template'))
                            ->required(),
                        Toggle::make('is_active')
                            ->label(__('admin.fields.is_active'))
                            ->required()
                            ->default(true),
                    ])
                    ->columns(3),
                Section::make(__('admin.sections.settings'))
                    ->schema([
                        KeyValue::make('settings')
                            ->label(__('admin.fields.settings'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
