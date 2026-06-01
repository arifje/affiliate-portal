<?php

namespace App\Filament\Resources\Feeds\Pages;

use App\Filament\Resources\Feeds\FeedResource;
use App\Filament\Resources\Feeds\Pages\Concerns\AnalyzesFeedSource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFeed extends ViewRecord
{
    use AnalyzesFeedSource;

    protected static string $resource = FeedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->analyzeSourceAction(),
            $this->runImportAction(),
            EditAction::make(),
        ];
    }
}
