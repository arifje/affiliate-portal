<?php

namespace App\Filament\Resources\FeedFieldMappings\Pages;

use App\Filament\Resources\FeedFieldMappings\FeedFieldMappingResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFeedFieldMapping extends ViewRecord
{
    protected static string $resource = FeedFieldMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
