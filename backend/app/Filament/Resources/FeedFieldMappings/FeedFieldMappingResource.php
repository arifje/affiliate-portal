<?php

namespace App\Filament\Resources\FeedFieldMappings;

use App\Filament\Resources\FeedFieldMappings\Pages\CreateFeedFieldMapping;
use App\Filament\Resources\FeedFieldMappings\Pages\EditFeedFieldMapping;
use App\Filament\Resources\FeedFieldMappings\Pages\ListFeedFieldMappings;
use App\Filament\Resources\FeedFieldMappings\Pages\ViewFeedFieldMapping;
use App\Filament\Resources\FeedFieldMappings\Schemas\FeedFieldMappingForm;
use App\Filament\Resources\FeedFieldMappings\Schemas\FeedFieldMappingInfolist;
use App\Filament\Resources\FeedFieldMappings\Tables\FeedFieldMappingsTable;
use App\Filament\Support\HasTranslatedResourceNavigation;
use App\Models\FeedFieldMapping;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FeedFieldMappingResource extends Resource
{
    use HasTranslatedResourceNavigation;

    protected static ?string $model = FeedFieldMapping::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string $translationKey = 'feed_field_mappings';

    protected static string $navigationGroupTranslationKey = 'admin.navigation.feed_imports';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return FeedFieldMappingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FeedFieldMappingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FeedFieldMappingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFeedFieldMappings::route('/'),
            'create' => CreateFeedFieldMapping::route('/create'),
            'view' => ViewFeedFieldMapping::route('/{record}'),
            'edit' => EditFeedFieldMapping::route('/{record}/edit'),
        ];
    }
}
