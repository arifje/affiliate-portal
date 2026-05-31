<?php

namespace App\Filament\Pages;

use App\Support\LaravelLogReader;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class Logs extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentMagnifyingGlass;

    protected static ?int $navigationSort = 101;

    protected static ?string $slug = 'logs';

    protected string $view = 'filament.pages.logs';

    public ?string $logFile = null;

    public ?string $level = null;

    public ?string $date = null;

    public ?string $search = null;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.navigation.administration');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.pages.logs.navigation_label');
    }

    public function getTitle(): string|Htmlable
    {
        return __('admin.pages.logs.title');
    }

    public function mount(): void
    {
        $this->logFile = $this->getLogFiles()[0]['name'] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public function getLevelOptions(): array
    {
        return [
            'EMERGENCY' => __('admin.pages.logs.levels.emergency'),
            'ALERT' => __('admin.pages.logs.levels.alert'),
            'CRITICAL' => __('admin.pages.logs.levels.critical'),
            'ERROR' => __('admin.pages.logs.levels.error'),
            'WARNING' => __('admin.pages.logs.levels.warning'),
            'NOTICE' => __('admin.pages.logs.levels.notice'),
            'INFO' => __('admin.pages.logs.levels.info'),
            'DEBUG' => __('admin.pages.logs.levels.debug'),
        ];
    }

    /**
     * @return array<int, array{name: string, size: int, formatted_size: string, updated_at: string|null}>
     */
    public function getLogFiles(): array
    {
        return $this->reader()->files();
    }

    /**
     * @return array{name: string|null, size: int, formatted_size: string, updated_at: string|null, path: string|null}
     */
    public function getLogMeta(): array
    {
        return $this->reader()->meta($this->logFile);
    }

    /**
     * @return array<int, array{timestamp: string, environment: string, level: string, message: string, context: string, context_line_count: int, truncated: bool}>
     */
    public function getLogEntries(): array
    {
        return $this->reader()->entries($this->logFile, [
            'level' => $this->level,
            'date' => $this->date,
            'search' => $this->search,
        ], 150);
    }

    /**
     * @return array<string, string>
     */
    public function getDebugInfo(): array
    {
        return [
            __('admin.pages.logs.debug.environment') => app()->environment(),
            __('admin.pages.logs.debug.laravel') => app()->version(),
            __('admin.pages.logs.debug.php') => PHP_VERSION,
            __('admin.pages.logs.debug.log_channel') => (string) config('logging.default'),
        ];
    }

    public function resetFilters(): void
    {
        $this->level = null;
        $this->date = null;
        $this->search = null;
    }

    public function getLevelColor(string $level): string
    {
        return match ($level) {
            'EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR' => 'danger',
            'WARNING' => 'warning',
            'NOTICE', 'INFO' => 'info',
            default => 'gray',
        };
    }

    private function reader(): LaravelLogReader
    {
        return app(LaravelLogReader::class);
    }
}
