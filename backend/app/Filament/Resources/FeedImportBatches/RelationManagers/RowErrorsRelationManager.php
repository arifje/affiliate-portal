<?php

namespace App\Filament\Resources\FeedImportBatches\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\CodeEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RowErrorsRelationManager extends RelationManager
{
    protected static string $relationship = 'rowErrors';

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('row_number')
                    ->numeric(),
                TextEntry::make('external_id')
                    ->placeholder('-'),
                CodeEntry::make('errors')
                    ->grammar('json')
                    ->columnSpanFull(),
                CodeEntry::make('source_payload')
                    ->grammar('json')
                    ->placeholder('-')
                    ->columnSpanFull(),
                CodeEntry::make('mapped_payload')
                    ->grammar('json')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('row_number')
            ->columns([
                TextColumn::make('row_number')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('external_id')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
