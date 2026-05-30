<?php

namespace App\Filament\Resources\FeedMappingProfiles\Pages;

use App\Filament\Resources\FeedMappingProfiles\FeedMappingProfileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFeedMappingProfiles extends ListRecords
{
    protected static string $resource = FeedMappingProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
