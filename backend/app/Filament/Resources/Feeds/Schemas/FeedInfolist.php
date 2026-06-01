<?php

namespace App\Filament\Resources\Feeds\Schemas;

use App\Filament\Support\JsonTextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FeedInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('admin.sections.feed'))
                    ->schema([
                        TextEntry::make('site.name')
                            ->label(__('admin.fields.site')),
                        TextEntry::make('partner.name')
                            ->label(__('admin.fields.partner')),
                        TextEntry::make('name')
                            ->label(__('admin.fields.name')),
                        TextEntry::make('slug')
                            ->label(__('admin.fields.slug')),
                        TextEntry::make('provider')
                            ->label(__('admin.fields.platform'))
                            ->badge(),
                        TextEntry::make('source_type')
                            ->label(__('admin.fields.source_type'))
                            ->badge(),
                        TextEntry::make('unique_identifier_field')
                            ->label(__('admin.fields.unique_identifier_field'))
                            ->placeholder('external_id'),
                        IconEntry::make('is_active')
                            ->label(__('admin.fields.is_active'))
                            ->boolean(),
                    ])
                    ->columnSpanFull()
                    ->columns([
                        'md' => 2,
                        'xl' => 4,
                    ]),
                Section::make(__('admin.sections.source'))
                    ->schema([
                        TextEntry::make('source_url')
                            ->label(__('admin.fields.source_url'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('source_file_path')
                            ->label(__('admin.fields.source_file'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('source_format')
                            ->label(__('admin.fields.source_format'))
                            ->badge(),
                        TextEntry::make('source_encoding')
                            ->label(__('admin.fields.source_encoding')),
                        TextEntry::make('row_selector')
                            ->label(__('admin.fields.primary_element'))
                            ->placeholder('-'),
                        TextEntry::make('credentials')
                            ->label(__('admin.fields.credentials'))
                            ->formatStateUsing(fn (?array $state): string => filled($state) ? __('admin.messages.configured') : __('admin.messages.not_configured'))
                            ->placeholder(__('admin.messages.not_configured')),
                        TextEntry::make('request_headers')
                            ->label(__('admin.fields.request_headers'))
                            ->formatStateUsing(fn (?array $state): string => filled($state) ? __('admin.messages.configured') : __('admin.messages.not_configured'))
                            ->placeholder(__('admin.messages.not_configured')),
                        TextEntry::make('request_query_params')
                            ->label(__('admin.fields.request_query_params'))
                            ->formatStateUsing(fn (?array $state): string => filled($state) ? __('admin.messages.configured') : __('admin.messages.not_configured'))
                            ->placeholder(__('admin.messages.not_configured')),
                    ])
                    ->columnSpanFull()
                    ->columns([
                        'md' => 2,
                        'xl' => 3,
                    ]),
                Section::make(__('admin.sections.feed_discovery'))
                    ->description(__('admin.helpers.feed_discovery'))
                    ->schema([
                        TextEntry::make('last_analyzed_at')
                            ->label(__('admin.fields.last_analyzed_at'))
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('row_selector')
                            ->label(__('admin.fields.primary_element'))
                            ->placeholder('-'),
                        JsonTextEntry::make('available_elements', maxHeight: '18rem')
                            ->label(__('admin.fields.available_elements'))
                            ->placeholder(__('admin.placeholders.analyze_feed_first'))
                            ->columnSpan(1),
                        JsonTextEntry::make('sample_fields', maxHeight: '18rem')
                            ->label(__('admin.fields.sample_fields'))
                            ->placeholder(__('admin.placeholders.analyze_feed_first'))
                            ->columnSpan(1),
                    ])
                    ->columnSpanFull()
                    ->columns([
                        'md' => 2,
                    ]),
                Section::make(__('admin.sections.mapping_and_import_state'))
                    ->schema([
                        TextEntry::make('schedule')
                            ->label(__('admin.fields.schedule'))
                            ->placeholder('-'),
                        TextEntry::make('last_import_status')
                            ->label(__('admin.fields.last_import_status'))
                            ->badge()
                            ->placeholder(__('admin.placeholders.never')),
                        TextEntry::make('last_import_started_at')
                            ->label(__('admin.fields.last_import_started_at'))
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('last_import_finished_at')
                            ->label(__('admin.fields.last_import_finished_at'))
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('last_import_message')
                            ->label(__('admin.fields.last_import_message'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull()
                    ->columns([
                        'md' => 2,
                    ]),
                Section::make(__('admin.sections.import_strategy'))
                    ->schema([
                        IconEntry::make('import_create_new')
                            ->label(__('admin.fields.import_create_new'))
                            ->boolean(),
                        IconEntry::make('import_update_existing')
                            ->label(__('admin.fields.import_update_existing'))
                            ->boolean(),
                        IconEntry::make('import_disable_missing_globally')
                            ->label(__('admin.fields.import_disable_missing_globally'))
                            ->boolean(),
                        IconEntry::make('import_disable_missing_for_site')
                            ->label(__('admin.fields.import_disable_missing_for_site'))
                            ->boolean(),
                        IconEntry::make('import_delete_missing')
                            ->label(__('admin.fields.import_delete_missing'))
                            ->boolean(),
                        IconEntry::make('import_update_search_indexes')
                            ->label(__('admin.fields.import_update_search_indexes'))
                            ->boolean(),
                        TextEntry::make('import_strategy_notes')
                            ->label(__('admin.fields.import_strategy_notes'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull()
                    ->columns([
                        'md' => 2,
                    ]),
                Section::make(__('admin.sections.timestamps'))
                    ->schema([
                        TextEntry::make('created_at')
                            ->label(__('admin.fields.created_at'))
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->label(__('admin.fields.updated_at'))
                            ->dateTime()
                            ->placeholder('-'),
                    ])
                    ->columnSpanFull()
                    ->columns([
                        'md' => 2,
                    ]),
            ]);
    }
}
