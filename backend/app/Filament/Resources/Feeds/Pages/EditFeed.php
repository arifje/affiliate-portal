<?php

namespace App\Filament\Resources\Feeds\Pages;

use App\Filament\Resources\Feeds\FeedResource;
use App\Filament\Resources\Feeds\Pages\Concerns\AnalyzesFeedSource;
use App\Filament\Resources\Feeds\Schemas\FeedForm;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\Concerns\HasWizard;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Wizard\Step;

class EditFeed extends EditRecord
{
    use AnalyzesFeedSource;
    use HasWizard;

    protected static string $resource = FeedResource::class;

    /**
     * @return array<int, Step>
     */
    public function getSteps(): array
    {
        return FeedForm::steps();
    }

    protected function hasSkippableSteps(): bool
    {
        return true;
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->analyzeSourceAction(),
            $this->runImportAction(),
            ViewAction::make(),
            DeleteAction::make()
                ->modal(false)
                ->requiresConfirmation(false)
                ->extraAttributes(['wire:confirm' => __('admin.messages.deleting_feed')])
                ->successRedirectUrl(FeedResource::getUrl()),
        ];
    }
}
