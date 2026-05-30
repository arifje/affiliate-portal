<?php

namespace App\Filament\Resources\FeedImportBatches\Pages;

use App\Filament\Resources\FeedImportBatches\FeedImportBatchResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditFeedImportBatch extends EditRecord
{
    protected static string $resource = FeedImportBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
