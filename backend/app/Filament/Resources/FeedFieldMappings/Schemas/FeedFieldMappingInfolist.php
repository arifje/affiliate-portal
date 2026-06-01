<?php

namespace App\Filament\Resources\FeedFieldMappings\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class FeedFieldMappingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('feed_mapping_profile_id')
                    ->label(__('admin.fields.mapping_profile'))
                    ->numeric(),
                TextEntry::make('canonicalField.id')
                    ->label(__('admin.fields.canonical_field')),
                TextEntry::make('mapping_action')
                    ->label(__('admin.fields.mapping_action'))
                    ->badge(),
                TextEntry::make('source_field')
                    ->label(__('admin.fields.source_field'))
                    ->placeholder('-'),
                TextEntry::make('source_sample')
                    ->label(__('admin.fields.source_sample'))
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('source_path')
                    ->label(__('admin.fields.source_path'))
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('fallback_fields')
                    ->label(__('admin.fields.fallback_fields'))
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('default_value')
                    ->label(__('admin.fields.default_value'))
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('transform_type')
                    ->label(__('admin.fields.transform_type')),
                TextEntry::make('transform_config')
                    ->label(__('admin.fields.transform_config'))
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('notes')
                    ->label(__('admin.fields.notes'))
                    ->placeholder('-')
                    ->columnSpanFull(),
                IconEntry::make('is_required')
                    ->label(__('admin.fields.is_required'))
                    ->boolean(),
                TextEntry::make('sort_order')
                    ->label(__('admin.fields.sort_order'))
                    ->numeric(),
                TextEntry::make('created_at')
                    ->label(__('admin.fields.created_at'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label(__('admin.fields.updated_at'))
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
