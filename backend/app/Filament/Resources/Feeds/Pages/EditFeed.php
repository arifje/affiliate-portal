<?php

namespace App\Filament\Resources\Feeds\Pages;

use App\Filament\Resources\Feeds\FeedResource;
use App\Filament\Resources\Feeds\Pages\Concerns\AnalyzesFeedSource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditFeed extends EditRecord
{
    use AnalyzesFeedSource;

    protected static string $resource = FeedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->analyzeSourceAction(),
            $this->mappingSetupAction(),
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
