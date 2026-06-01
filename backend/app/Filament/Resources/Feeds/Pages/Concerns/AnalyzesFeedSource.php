<?php

namespace App\Filament\Resources\Feeds\Pages\Concerns;

use App\Models\Feed;
use App\Services\Feeds\FeedStructureAnalyzer;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Throwable;

trait AnalyzesFeedSource
{
    protected function analyzeSourceAction(): Action
    {
        return Action::make('analyzeSource')
            ->label(__('admin.actions.analyze_feed_source'))
            ->icon(Heroicon::OutlinedMagnifyingGlass)
            ->requiresConfirmation()
            ->modalDescription(__('admin.messages.analyze_feed_source'))
            ->action(fn () => $this->analyzeFeedSource());
    }

    protected function analyzeFeedSource(): void
    {
        /** @var Feed $feed */
        $feed = $this->getRecord();

        if (! $feed->mappingProfile) {
            Notification::make()
                ->danger()
                ->title(__('admin.messages.mapping_profile_required'))
                ->send();

            return;
        }

        try {
            $analysis = app(FeedStructureAnalyzer::class)->analyze($feed);
        } catch (Throwable $exception) {
            Notification::make()
                ->danger()
                ->title(__('admin.messages.feed_analysis_failed'))
                ->body($exception->getMessage())
                ->send();

            return;
        }

        $feed->mappingProfile->forceFill([
            'row_selector' => $analysis['row_selector'],
            'available_elements' => $analysis['available_elements'],
            'sample_fields' => $analysis['sample_fields'],
            'sample_payload' => $analysis['sample_payload'],
            'last_analyzed_at' => now(),
        ])->save();

        Notification::make()
            ->success()
            ->title(__('admin.messages.feed_analysis_completed', [
                'fields' => count($analysis['sample_fields']),
                'elements' => count($analysis['available_elements']),
            ]))
            ->send();
    }
}
