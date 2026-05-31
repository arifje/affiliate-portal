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
                TextEntry::make('key')
                    ->label(__('admin.fields.key')),
                TextEntry::make('field_group')
                    ->label(__('admin.fields.field_group')),
                TextEntry::make('label')
                    ->label(__('admin.fields.label')),
                TextEntry::make('description')
                    ->label(__('admin.fields.description'))
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('data_type')
                    ->label(__('admin.fields.data_type')),
                TextEntry::make('target_column')
                    ->label(__('admin.fields.target_column'))
                    ->placeholder('-'),
                TextEntry::make('metadata_path')
                    ->label(__('admin.fields.metadata_path'))
                    ->placeholder('-'),
                IconEntry::make('is_required')
                    ->label(__('admin.fields.is_required'))
                    ->boolean(),
                IconEntry::make('is_searchable')
                    ->label(__('admin.fields.is_searchable'))
                    ->boolean(),
                IconEntry::make('is_filterable')
                    ->label(__('admin.fields.is_filterable'))
                    ->boolean(),
                IconEntry::make('is_variant')
                    ->label(__('admin.fields.is_variant'))
                    ->boolean(),
                TextEntry::make('validation_rules')
                    ->label(__('admin.fields.validation_rules'))
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('options')
                    ->label(__('admin.fields.options'))
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('sort_order')
                    ->label(__('admin.fields.sort_order'))
                    ->numeric(),
                IconEntry::make('is_active')
                    ->label(__('admin.fields.is_active'))
                    ->boolean(),
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
