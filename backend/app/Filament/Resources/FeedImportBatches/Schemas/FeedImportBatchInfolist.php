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
                    ->label(__('admin.fields.feed')),
                TextEntry::make('status')
                    ->label(__('admin.fields.status')),
                TextEntry::make('source_url')
                    ->label(__('admin.fields.source_url'))
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('total_rows')
                    ->label(__('admin.fields.total_rows'))
                    ->numeric(),
                TextEntry::make('processed_rows')
                    ->label(__('admin.fields.processed_rows'))
                    ->numeric(),
                TextEntry::make('created_rows')
                    ->label(__('admin.fields.created_rows'))
                    ->numeric(),
                TextEntry::make('updated_rows')
                    ->label(__('admin.fields.updated_rows'))
                    ->numeric(),
                TextEntry::make('skipped_rows')
                    ->label(__('admin.fields.skipped_rows'))
                    ->numeric(),
                TextEntry::make('failed_rows')
                    ->label(__('admin.fields.failed_rows'))
                    ->numeric(),
                TextEntry::make('started_at')
                    ->label(__('admin.fields.started_at'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('finished_at')
                    ->label(__('admin.fields.finished_at'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('error_message')
                    ->label(__('admin.fields.error_message'))
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('metrics')
                    ->label(__('admin.fields.metrics'))
                    ->placeholder('-')
                    ->columnSpanFull(),
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
