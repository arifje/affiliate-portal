<?php

use App\Models\Click;
use App\Models\Feed;
use App\Models\FeedImportBatch;
use App\Models\FeedImportRowError;
use App\Models\FeedMappingProfile;
use App\Models\FeedProductFieldMapping;
use App\Models\Partner;
use App\Models\Product;
use App\Models\ProductView;
use App\Services\Feeds\FeedImporter;
use Database\Seeders\Demo\DemoHartslagmetersSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
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

Artisan::command('demo:reset-affiliate-data {--no-seed} {--force}', function (): int {
    if (app()->isProduction() && ! $this->option('force')) {
        $this->error('Refusing to reset affiliate data in production without --force.');

        return 1;
    }

    DB::transaction(function (): void {
        Click::query()->delete();
        ProductView::query()->delete();
        FeedImportRowError::query()->delete();
        FeedImportBatch::query()->delete();
        FeedProductFieldMapping::query()->delete();
        Product::query()->delete();
        Feed::query()->delete();
        FeedMappingProfile::query()->where('is_template', false)->delete();
        Partner::query()->delete();
    });

    $this->info('Deleted feeds, partners, products and related import/click/view data.');

    if (! $this->option('no-seed')) {
        $this->call('db:seed', [
            '--class' => DemoHartslagmetersSeeder::class,
            '--force' => true,
        ]);

        $this->info('Seeded demo partner, Awin-style CSV feed, product mappings and demo products.');
    }

    return 0;
})->purpose('Reset affiliate demo data and optionally seed the hartslagmeters demo catalog.');

Schedule::command('feeds:run --scheduled')
    ->everyMinute()
    ->withoutOverlapping();
