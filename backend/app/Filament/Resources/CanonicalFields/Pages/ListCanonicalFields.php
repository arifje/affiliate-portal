<?php

namespace App\Filament\Resources\CanonicalFields\Pages;

use App\Filament\Resources\CanonicalFields\CanonicalFieldResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCanonicalFields extends ListRecords
{
    protected static string $resource = CanonicalFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
