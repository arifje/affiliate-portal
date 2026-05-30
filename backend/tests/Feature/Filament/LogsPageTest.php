<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\Logs;
use App\Models\User;
use App\Support\LaravelLogReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

class LogsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_logs_page_can_be_rendered(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $this
            ->actingAs($user)
            ->get('/admin/logs')
            ->assertOk()
            ->assertSee('Debug info')
            ->assertSee('Laravel logs');
    }

    public function test_logs_can_be_filtered_by_level_date_and_search(): void
    {
        $directory = storage_path('framework/testing/logs-'.uniqid());

        File::ensureDirectoryExists($directory);
        File::put($directory.'/laravel.log', implode(PHP_EOL, [
            '[2026-05-30 10:00:00] local.INFO: Feed import completed',
            '[2026-05-30 11:00:00] local.ERROR: Awin feed failed',
            '#0 /var/www/backend/app/Jobs/ImportFeed.php(12): example',
            '[2026-05-31 12:00:00] local.WARNING: Slow response detected',
        ]));

        $this->app->instance(LaravelLogReader::class, new LaravelLogReader($directory));

        Livewire::test(Logs::class)
            ->set('level', 'ERROR')
            ->set('date', '2026-05-30')
            ->set('search', 'awin')
            ->assertSee('Awin feed failed')
            ->assertDontSee('Feed import completed')
            ->assertDontSee('Slow response detected');

        File::deleteDirectory($directory);
    }
}
