<?php

namespace App\Filament\Resources\FeedImportBatches\Pages;

use App\Filament\Resources\FeedImportBatches\FeedImportBatchResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFeedImportBatch extends ViewRecord
{
    protected static string $resource = FeedImportBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
