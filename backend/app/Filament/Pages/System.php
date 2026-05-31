<?php

namespace App\Filament\Pages;

use App\Support\SystemInspector;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class System extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    protected static ?int $navigationSort = 102;

    protected static ?string $slug = 'system';

    protected string $view = 'filament.pages.system';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.navigation.administration');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.pages.system.navigation_label');
    }

    public function getTitle(): string|Htmlable
    {
        return __('admin.pages.system.title');
    }

    /**
     * @return array<int, array{label: string, value: string, description: string|null}>
     */
    public function getServerInfo(): array
    {
        return $this->inspector()->serverInfo();
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public function getApplicationInfo(): array
    {
        return $this->inspector()->applicationInfo();
    }

    /**
     * @return array<int, array{label: string, value: string, status: string, description: string|null}>
     */
    public function getRequirements(): array
    {
        return $this->inspector()->requirements();
    }

    /**
     * @return array<int, array{label: string, value: string, status: string, description: string|null}>
     */
    public function getPhpExtensions(): array
    {
        return $this->inspector()->phpExtensions();
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public function getStorageInfo(): array
    {
        return $this->inspector()->storageInfo();
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public function getServiceInfo(): array
    {
        return $this->inspector()->serviceInfo();
    }

    public function statusColor(string $status): string
    {
        return match ($status) {
            'pass' => 'success',
            'warning' => 'warning',
            default => 'danger',
        };
    }

    public function statusIcon(string $status): Heroicon
    {
        return match ($status) {
            'pass' => Heroicon::OutlinedCheckCircle,
            'warning' => Heroicon::OutlinedExclamationTriangle,
            default => Heroicon::OutlinedXCircle,
        };
    }

    private function inspector(): SystemInspector
    {
        return app(SystemInspector::class);
    }
}
