<?php

namespace App\Filament\Resources\FeedMappingProfiles\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class FeedMappingProfileInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('site.name')
                    ->label('Site')
                    ->placeholder('-'),
                TextEntry::make('partner.name')
                    ->label('Partner')
                    ->placeholder('-'),
                TextEntry::make('name'),
                TextEntry::make('slug'),
                TextEntry::make('provider'),
                TextEntry::make('source_format'),
                TextEntry::make('source_encoding'),
                TextEntry::make('delimiter')
                    ->placeholder('-'),
                TextEntry::make('enclosure')
                    ->placeholder('-'),
                TextEntry::make('decimal_separator'),
                TextEntry::make('thousands_separator')
                    ->placeholder('-'),
                TextEntry::make('currency'),
                TextEntry::make('locale'),
                TextEntry::make('timezone'),
                TextEntry::make('row_selector')
                    ->placeholder('-'),
                IconEntry::make('first_row_is_header')
                    ->boolean(),
                IconEntry::make('is_template')
                    ->boolean(),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('settings')
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
