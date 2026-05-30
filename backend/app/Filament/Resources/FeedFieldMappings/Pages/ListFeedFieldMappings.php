<?php

namespace App\Filament\Resources\FeedFieldMappings\Pages;

use App\Filament\Resources\FeedFieldMappings\FeedFieldMappingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFeedFieldMappings extends ListRecords
{
    protected static string $resource = FeedFieldMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
