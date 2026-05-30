<?php

namespace App\Filament\Pages;

use App\Support\LaravelLogReader;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Logs extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentMagnifyingGlass;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Logs';

    protected static ?int $navigationSort = 101;

    protected static ?string $slug = 'logs';

    protected static ?string $title = 'Logs';

    protected string $view = 'filament.pages.logs';

    public ?string $logFile = null;

    public ?string $level = null;

    public ?string $date = null;

    public ?string $search = null;

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
            'EMERGENCY' => 'Emergency',
            'ALERT' => 'Alert',
            'CRITICAL' => 'Critical',
            'ERROR' => 'Error',
            'WARNING' => 'Warning',
            'NOTICE' => 'Notice',
            'INFO' => 'Info',
            'DEBUG' => 'Debug',
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
            'Environment' => app()->environment(),
            'Laravel' => app()->version(),
            'PHP' => PHP_VERSION,
            'Log channel' => (string) config('logging.default'),
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
