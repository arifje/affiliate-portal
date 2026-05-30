<?php

namespace App\Filament\Resources\CanonicalFields\Pages;

use App\Filament\Resources\CanonicalFields\CanonicalFieldResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCanonicalField extends EditRecord
{
    protected static string $resource = CanonicalFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
