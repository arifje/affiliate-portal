<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Sites\SiteResource;
use App\Models\Product;
use App\Models\Site;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SiteStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 4;

    protected ?string $pollingInterval = '30s';

    protected function getHeading(): ?string
    {
        return __('admin.widgets.sites.heading');
    }

    protected function getDescription(): ?string
    {
        return __('admin.widgets.sites.description');
    }

    protected function getStats(): array
    {
        $totalSites = Site::query()->count();
        $activeSites = Site::query()->where('is_active', true)->count();
        $inactiveSites = $totalSites - $activeSites;
        $sitesWithProducts = Site::query()->has('products')->count();
        $productsAssigned = Product::query()->whereNotNull('site_id')->count();

        return [
            Stat::make(__('admin.widgets.sites.total'), number_format($totalSites))
                ->description(__('admin.widgets.common.active_inactive', [
                    'active' => number_format($activeSites),
                    'inactive' => number_format($inactiveSites),
                ]))
                ->icon(Heroicon::OutlinedGlobeAlt)
                ->url(SiteResource::getUrl())
                ->color('primary'),
            Stat::make(__('admin.widgets.sites.active'), number_format($activeSites))
                ->description($totalSites > 0 ? __('admin.widgets.sites.site_percentage', ['percentage' => round(($activeSites / $totalSites) * 100)]) : __('admin.widgets.sites.empty'))
                ->icon(Heroicon::OutlinedCheckCircle)
                ->url(SiteResource::getUrl())
                ->color('success'),
            Stat::make(__('admin.widgets.sites.with_products'), number_format($sitesWithProducts))
                ->description(__('admin.widgets.sites.products_assigned', ['count' => number_format($productsAssigned)]))
                ->icon(Heroicon::OutlinedRectangleGroup)
                ->url(SiteResource::getUrl())
                ->color('info'),
        ];
    }
}
