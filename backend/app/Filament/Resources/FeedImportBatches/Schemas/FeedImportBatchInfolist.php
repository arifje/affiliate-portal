<?php

namespace App\Filament\Resources\FeedImportBatches\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class FeedImportBatchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('feed.name')
                    ->label('Feed'),
                TextEntry::make('feed_mapping_profile_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('status'),
                TextEntry::make('source_url')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('total_rows')
                    ->numeric(),
                TextEntry::make('processed_rows')
                    ->numeric(),
                TextEntry::make('created_rows')
                    ->numeric(),
                TextEntry::make('updated_rows')
                    ->numeric(),
                TextEntry::make('skipped_rows')
                    ->numeric(),
                TextEntry::make('failed_rows')
                    ->numeric(),
                TextEntry::make('started_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('finished_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('error_message')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('metrics')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
