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
                    ->label(__('admin.fields.site'))
                    ->placeholder('-'),
                TextEntry::make('partner.name')
                    ->label(__('admin.fields.partner'))
                    ->placeholder('-'),
                TextEntry::make('name')
                    ->label(__('admin.fields.name')),
                TextEntry::make('slug')
                    ->label(__('admin.fields.slug')),
                TextEntry::make('provider')
                    ->label(__('admin.fields.provider')),
                TextEntry::make('source_format')
                    ->label(__('admin.fields.source_format')),
                TextEntry::make('source_encoding')
                    ->label(__('admin.fields.source_encoding')),
                TextEntry::make('delimiter')
                    ->label(__('admin.fields.delimiter'))
                    ->placeholder('-'),
                TextEntry::make('enclosure')
                    ->label(__('admin.fields.enclosure'))
                    ->placeholder('-'),
                TextEntry::make('decimal_separator')
                    ->label(__('admin.fields.decimal_separator')),
                TextEntry::make('thousands_separator')
                    ->label(__('admin.fields.thousands_separator'))
                    ->placeholder('-'),
                TextEntry::make('currency')
                    ->label(__('admin.fields.currency')),
                TextEntry::make('locale')
                    ->label(__('admin.fields.locale')),
                TextEntry::make('timezone')
                    ->label(__('admin.fields.timezone')),
                TextEntry::make('row_selector')
                    ->label(__('admin.fields.row_selector'))
                    ->placeholder('-'),
                IconEntry::make('first_row_is_header')
                    ->label(__('admin.fields.first_row_is_header'))
                    ->boolean(),
                IconEntry::make('is_template')
                    ->label(__('admin.fields.is_template'))
                    ->boolean(),
                IconEntry::make('is_active')
                    ->label(__('admin.fields.is_active'))
                    ->boolean(),
                TextEntry::make('settings')
                    ->label(__('admin.fields.settings'))
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
