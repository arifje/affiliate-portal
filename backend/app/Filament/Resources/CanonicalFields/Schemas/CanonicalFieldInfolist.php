<?php

namespace App\Filament\Resources\CanonicalFields\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CanonicalFieldInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('key'),
                TextEntry::make('field_group'),
                TextEntry::make('label'),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('data_type'),
                TextEntry::make('target_column')
                    ->placeholder('-'),
                TextEntry::make('metadata_path')
                    ->placeholder('-'),
                IconEntry::make('is_required')
                    ->boolean(),
                IconEntry::make('is_searchable')
                    ->boolean(),
                IconEntry::make('is_filterable')
                    ->boolean(),
                IconEntry::make('is_variant')
                    ->boolean(),
                TextEntry::make('validation_rules')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('options')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('sort_order')
                    ->numeric(),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
