<?php

namespace App\Filament\Resources\Feeds;

use App\Filament\Resources\Feeds\Pages\CreateFeed;
use App\Filament\Resources\Feeds\Pages\EditFeed;
use App\Filament\Resources\Feeds\Pages\ListFeeds;
use App\Filament\Resources\Feeds\Pages\ViewFeed;
use App\Filament\Resources\Feeds\RelationManagers\ProductFieldMappingsRelationManager;
use App\Filament\Resources\Feeds\Schemas\FeedForm;
use App\Filament\Resources\Feeds\Schemas\FeedInfolist;
use App\Filament\Resources\Feeds\Tables\FeedsTable;
use App\Filament\Support\HasTranslatedResourceNavigation;
use App\Models\Feed;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FeedResource extends Resource
{
    use HasTranslatedResourceNavigation;

    protected static ?string $model = Feed::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCloudArrowDown;

    protected static string $translationKey = 'feeds';

    protected static string $navigationGroupTranslationKey = 'admin.navigation.feed_imports';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return FeedForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FeedInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FeedsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ProductFieldMappingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFeeds::route('/'),
            'create' => CreateFeed::route('/create'),
            'view' => ViewFeed::route('/{record}'),
            'edit' => EditFeed::route('/{record}/edit'),
        ];
    }
}
