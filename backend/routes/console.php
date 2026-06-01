<?php

use App\Models\Feed;
use App\Services\Feeds\FeedImporter;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('feeds:run {feed?} {--scheduled}', function (): int {
    $identifier = $this->argument('feed');
    $scheduledOnly = (bool) $this->option('scheduled');
    $query = Feed::query()->where('is_active', true);

    if ($identifier) {
        $query->where(function ($query) use ($identifier): void {
            $query->whereKey($identifier)
                ->orWhere('slug', $identifier);
        });
    }

    if ($scheduledOnly) {
        $query->whereNotNull('schedule');
    }

    $feeds = $query->get()
        ->filter(fn (Feed $feed): bool => ! $scheduledOnly || $feed->isImportDue());

    if ($feeds->isEmpty()) {
        $this->info('No feeds to import.');

        return 0;
    }

    $importer = app(FeedImporter::class);
    $failed = 0;

    foreach ($feeds as $feed) {
        $this->line("Running feed {$feed->id}: {$feed->name}");

        try {
            $batch = $importer->import($feed);
            $this->info("Finished batch {$batch->id}: {$batch->created_rows} created, {$batch->updated_rows} updated, {$batch->failed_rows} failed.");
        } catch (Throwable $exception) {
            $failed++;
            $this->error("Feed {$feed->id} failed: {$exception->getMessage()}");
        }
    }

    return $failed > 0 ? 1 : 0;
})->purpose('Run one feed import, or all due scheduled feed imports.');

Schedule::command('feeds:run --scheduled')
    ->everyMinute()
    ->withoutOverlapping();
