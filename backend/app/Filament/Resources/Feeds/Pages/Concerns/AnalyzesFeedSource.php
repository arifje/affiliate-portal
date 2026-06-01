<?php

namespace App\Filament\Resources\Feeds\Pages\Concerns;

use App\Models\Feed;
use App\Services\Feeds\FeedImporter;
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

    protected function runImportAction(): Action
    {
        return Action::make('runImport')
            ->label(__('admin.actions.run_import'))
            ->icon(Heroicon::OutlinedPlay)
            ->requiresConfirmation()
            ->modalDescription(__('admin.messages.run_feed_import'))
            ->action(fn () => $this->runFeedImport());
    }

    protected function analyzeFeedSource(): void
    {
        /** @var Feed $feed */
        $feed = $this->getRecord();

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

        $feed->forceFill([
            'row_selector' => $analysis['row_selector'],
            'available_elements' => $analysis['available_elements'],
            'sample_fields' => $analysis['sample_fields'],
            'sample_payload' => $analysis['sample_payload'],
            'last_analyzed_at' => now(),
        ])->saveQuietly();

        Notification::make()
            ->success()
            ->title(__('admin.messages.feed_analysis_completed', [
                'fields' => count($analysis['sample_fields']),
                'elements' => count($analysis['available_elements']),
            ]))
            ->send();
    }

    protected function runFeedImport(): void
    {
        /** @var Feed $feed */
        $feed = $this->getRecord();

        try {
            $batch = app(FeedImporter::class)->import($feed);
        } catch (Throwable $exception) {
            Notification::make()
                ->danger()
                ->title(__('admin.messages.feed_import_failed'))
                ->body($exception->getMessage())
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title(__('admin.messages.feed_import_completed', [
                'created' => $batch->created_rows,
                'updated' => $batch->updated_rows,
                'skipped' => $batch->skipped_rows,
                'failed' => $batch->failed_rows,
            ]))
            ->send();
    }
}
