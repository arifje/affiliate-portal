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
                    ->numeric(),
                TextEntry::make('canonicalField.id')
                    ->label('Canonical field'),
                TextEntry::make('source_field')
                    ->placeholder('-'),
                TextEntry::make('source_path')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('fallback_fields')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('default_value')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('transform_type'),
                TextEntry::make('transform_config')
                    ->placeholder('-')
                    ->columnSpanFull(),
                IconEntry::make('is_required')
                    ->boolean(),
                TextEntry::make('sort_order')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
