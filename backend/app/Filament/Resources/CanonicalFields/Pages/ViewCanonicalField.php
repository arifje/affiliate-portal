<?php

namespace App\Filament\Resources\CanonicalFields\Pages;

use App\Filament\Resources\CanonicalFields\CanonicalFieldResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCanonicalField extends ViewRecord
{
    protected static string $resource = CanonicalFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
