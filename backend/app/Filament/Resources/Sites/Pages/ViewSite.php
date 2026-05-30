<?php

namespace App\Filament\Resources\Sites\Pages;

use App\Filament\Resources\Sites\SiteResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewSite extends ViewRecord
{
    protected static string $resource = SiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->icon(Heroicon::OutlinedEye)
                ->url(fn (): string => SiteResource::getPreviewUrl($this->record))
                ->openUrlInNewTab(),
            EditAction::make(),
        ];
    }
}
