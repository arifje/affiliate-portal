<?php

namespace App\Filament\Resources\FeedMappingProfiles;

use App\Filament\Resources\FeedMappingProfiles\Pages\CreateFeedMappingProfile;
use App\Filament\Resources\FeedMappingProfiles\Pages\EditFeedMappingProfile;
use App\Filament\Resources\FeedMappingProfiles\Pages\ListFeedMappingProfiles;
use App\Filament\Resources\FeedMappingProfiles\Pages\ViewFeedMappingProfile;
use App\Filament\Resources\FeedMappingProfiles\RelationManagers\FieldMappingsRelationManager;
use App\Filament\Resources\FeedMappingProfiles\Schemas\FeedMappingProfileForm;
use App\Filament\Resources\FeedMappingProfiles\Schemas\FeedMappingProfileInfolist;
use App\Filament\Resources\FeedMappingProfiles\Tables\FeedMappingProfilesTable;
use App\Filament\Support\HasTranslatedResourceNavigation;
use App\Models\FeedMappingProfile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FeedMappingProfileResource extends Resource
{
    use HasTranslatedResourceNavigation;

    protected static ?string $model = FeedMappingProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $translationKey = 'feed_mapping_profiles';

    protected static string $navigationGroupTranslationKey = 'admin.navigation.feed_imports';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return FeedMappingProfileForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FeedMappingProfileInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FeedMappingProfilesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            FieldMappingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFeedMappingProfiles::route('/'),
            'create' => CreateFeedMappingProfile::route('/create'),
            'view' => ViewFeedMappingProfile::route('/{record}'),
            'edit' => EditFeedMappingProfile::route('/{record}/edit'),
        ];
    }
}
