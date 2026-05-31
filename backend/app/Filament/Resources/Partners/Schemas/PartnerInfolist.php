<?php

namespace App\Filament\Resources\Partners\Schemas;

use App\Filament\Support\JsonTextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PartnerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.partner'))
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('admin.fields.name')),
                        TextEntry::make('slug')
                            ->label(__('admin.fields.slug')),
                        TextEntry::make('provider')
                            ->label(__('admin.fields.provider'))
                            ->badge(),
                        TextEntry::make('website_url')
                            ->label(__('admin.fields.website_url'))
                            ->placeholder('-'),
                        IconEntry::make('is_active')
                            ->label(__('admin.fields.is_active'))
                            ->boolean(),
                    ])
                    ->columns(2),
                Section::make(__('admin.sections.settings'))
                    ->schema([
                        JsonTextEntry::make('settings')
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
                    ])
                    ->columns(2),
            ]);
    }
}
