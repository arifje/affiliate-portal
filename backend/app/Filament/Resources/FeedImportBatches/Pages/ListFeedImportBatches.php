<?php

namespace App\Filament\Resources\FeedImportBatches\Pages;

use App\Filament\Resources\FeedImportBatches\FeedImportBatchResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFeedImportBatches extends ListRecords
{
    protected static string $resource = FeedImportBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
