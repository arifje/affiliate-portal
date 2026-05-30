<?php

namespace App\Filament\Resources\FeedMappingProfiles\Pages;

use App\Filament\Resources\FeedMappingProfiles\FeedMappingProfileResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFeedMappingProfile extends ViewRecord
{
    protected static string $resource = FeedMappingProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
