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
                            ->label(__('admin.fields.provider'))
                            ->badge(),
                        TextEntry::make('source_type')
                            ->label(__('admin.fields.source_type'))
                            ->badge(),
                        TextEntry::make('mappingProfile.name')
                            ->label(__('admin.fields.mapping_profile'))
                            ->placeholder('-'),
                        IconEntry::make('is_active')
                            ->label(__('admin.fields.is_active'))
                            ->boolean(),
                    ])
                    ->columns(2),
                Section::make(__('admin.sections.source'))
                    ->schema([
                        TextEntry::make('source_url')
                            ->label(__('admin.fields.source_url'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('credentials')
                            ->label(__('admin.fields.credentials'))
                            ->formatStateUsing(fn (?array $state): string => filled($state) ? __('admin.messages.configured') : __('admin.messages.not_configured'))
                            ->placeholder(__('admin.messages.not_configured')),
                    ]),
                Section::make(__('admin.sections.mapping_and_import_state'))
                    ->schema([
                        JsonTextEntry::make('mapping')
                            ->label(__('admin.fields.mapping'))
                            ->placeholder('-')
                            ->columnSpanFull(),
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
                    ->columns(2),
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
                    ->columns(2),
            ]);
    }
}
