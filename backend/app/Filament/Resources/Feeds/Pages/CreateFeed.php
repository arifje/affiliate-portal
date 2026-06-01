<?php

namespace App\Filament\Resources\Feeds\Pages;

use App\Filament\Resources\Feeds\FeedResource;
use App\Filament\Resources\Feeds\Schemas\FeedForm;
use Filament\Resources\Pages\Concerns\HasWizard;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Wizard\Step;

class CreateFeed extends CreateRecord
{
    use HasWizard;

    protected static string $resource = FeedResource::class;

    /**
     * @return array<int, Step>
     */
    public function getSteps(): array
    {
        return FeedForm::steps();
    }
}
