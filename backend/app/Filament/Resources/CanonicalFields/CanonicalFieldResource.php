<?php

namespace App\Filament\Resources\CanonicalFields;

use App\Filament\Resources\CanonicalFields\Pages\CreateCanonicalField;
use App\Filament\Resources\CanonicalFields\Pages\EditCanonicalField;
use App\Filament\Resources\CanonicalFields\Pages\ListCanonicalFields;
use App\Filament\Resources\CanonicalFields\Pages\ViewCanonicalField;
use App\Filament\Resources\CanonicalFields\Schemas\CanonicalFieldForm;
use App\Filament\Resources\CanonicalFields\Schemas\CanonicalFieldInfolist;
use App\Filament\Resources\CanonicalFields\Tables\CanonicalFieldsTable;
use App\Models\CanonicalField;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CanonicalFieldResource extends Resource
{
    protected static ?string $model = CanonicalField::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|UnitEnum|null $navigationGroup = 'Feed imports';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'key';

    public static function form(Schema $schema): Schema
    {
        return CanonicalFieldForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CanonicalFieldInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CanonicalFieldsTable::configure($table);
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
            'index' => ListCanonicalFields::route('/'),
            'create' => CreateCanonicalField::route('/create'),
            'view' => ViewCanonicalField::route('/{record}'),
            'edit' => EditCanonicalField::route('/{record}/edit'),
        ];
    }
}
