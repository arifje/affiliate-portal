<?php

namespace App\Filament\Resources\FeedFieldMappings\Pages;

use App\Filament\Resources\FeedFieldMappings\FeedFieldMappingResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditFeedFieldMapping extends EditRecord
{
    protected static string $resource = FeedFieldMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
