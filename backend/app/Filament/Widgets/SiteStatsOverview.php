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
    protected static ?int $sort = 2;

    protected ?string $heading = 'Sites';

    protected ?string $description = 'Domain inventory and catalog assignment.';

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $totalSites = Site::query()->count();
        $activeSites = Site::query()->where('is_active', true)->count();
        $inactiveSites = $totalSites - $activeSites;
        $sitesWithProducts = Site::query()->has('products')->count();
        $productsAssigned = Product::query()->whereNotNull('site_id')->count();

        return [
            Stat::make('Total sites', number_format($totalSites))
                ->description(number_format($activeSites).' active, '.number_format($inactiveSites).' inactive')
                ->icon(Heroicon::OutlinedGlobeAlt)
                ->url(SiteResource::getUrl())
                ->color('primary'),
            Stat::make('Active sites', number_format($activeSites))
                ->description($totalSites > 0 ? round(($activeSites / $totalSites) * 100).'% of sites' : 'No sites yet')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->url(SiteResource::getUrl())
                ->color('success'),
            Stat::make('Sites with products', number_format($sitesWithProducts))
                ->description(number_format($productsAssigned).' products assigned')
                ->icon(Heroicon::OutlinedRectangleGroup)
                ->url(SiteResource::getUrl())
                ->color('info'),
        ];
    }
}
