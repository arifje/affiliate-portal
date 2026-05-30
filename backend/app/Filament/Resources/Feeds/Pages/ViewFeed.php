<?php

namespace App\Filament\Resources\Feeds\Pages;

use App\Filament\Resources\Feeds\FeedResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFeed extends ViewRecord
{
    protected static string $resource = FeedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
