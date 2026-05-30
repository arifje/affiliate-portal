<?php

namespace App\Filament\Resources\FeedMappingProfiles\Pages;

use App\Filament\Resources\FeedMappingProfiles\FeedMappingProfileResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditFeedMappingProfile extends EditRecord
{
    protected static string $resource = FeedMappingProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
