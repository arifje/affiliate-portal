<?php

namespace App\Filament\Resources\FeedImportBatches;

use App\Filament\Resources\FeedImportBatches\Pages\CreateFeedImportBatch;
use App\Filament\Resources\FeedImportBatches\Pages\EditFeedImportBatch;
use App\Filament\Resources\FeedImportBatches\Pages\ListFeedImportBatches;
use App\Filament\Resources\FeedImportBatches\Pages\ViewFeedImportBatch;
use App\Filament\Resources\FeedImportBatches\RelationManagers\RowErrorsRelationManager;
use App\Filament\Resources\FeedImportBatches\Schemas\FeedImportBatchForm;
use App\Filament\Resources\FeedImportBatches\Schemas\FeedImportBatchInfolist;
use App\Filament\Resources\FeedImportBatches\Tables\FeedImportBatchesTable;
use App\Filament\Support\HasTranslatedResourceNavigation;
use App\Models\FeedImportBatch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FeedImportBatchResource extends Resource
{
    use HasTranslatedResourceNavigation;

    protected static ?string $model = FeedImportBatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCloudArrowDown;

    protected static string $translationKey = 'feed_import_batches';

    protected static string $navigationGroupTranslationKey = 'admin.navigation.feed_imports';

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return FeedImportBatchForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FeedImportBatchInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FeedImportBatchesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RowErrorsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFeedImportBatches::route('/'),
            'create' => CreateFeedImportBatch::route('/create'),
            'view' => ViewFeedImportBatch::route('/{record}'),
            'edit' => EditFeedImportBatch::route('/{record}/edit'),
        ];
    }
}
