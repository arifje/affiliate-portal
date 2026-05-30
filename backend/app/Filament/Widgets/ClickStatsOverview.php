<?php

namespace App\Filament\Widgets;

use App\Models\Click;
use App\Models\Site;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ClickStatsOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    protected ?string $heading = 'Clicks';

    protected ?string $pollingInterval = '30s';

    protected function getDescription(): ?string
    {
        $site = $this->selectedSite();

        return $site
            ? 'Outbound click performance for '.$site->name.'.'
            : 'Outbound click performance across all sites.';
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total outbound clicks', number_format($this->clicks()->count()))
                ->description($this->rangeDescription())
                ->icon(Heroicon::OutlinedCursorArrowRays)
                ->chart($this->lastSevenDaysChart())
                ->color('primary'),
            Stat::make('Today', number_format($this->clicksSince(now()->startOfDay())))
                ->description('Since midnight')
                ->icon(Heroicon::OutlinedBolt)
                ->color('success'),
            Stat::make('Last 7 days', number_format($this->clicksSince(now()->subDays(6)->startOfDay())))
                ->description('Rolling week including today')
                ->icon(Heroicon::OutlinedChartBar)
                ->color('info'),
            Stat::make('Last 30 days', number_format($this->clicksSince(now()->subDays(29)->startOfDay())))
                ->description('Rolling month including today')
                ->icon(Heroicon::OutlinedPresentationChartLine)
                ->color('gray'),
        ];
    }

    private function rangeDescription(): string
    {
        return implode(' | ', [
            number_format($this->clicksSince(now()->startOfDay())).' today',
            number_format($this->clicksSince(now()->subDays(6)->startOfDay())).' 7d',
            number_format($this->clicksSince(now()->subDays(29)->startOfDay())).' 30d',
        ]);
    }

    private function clicksSince(Carbon $date): int
    {
        return $this->clicks()
            ->where('clicked_at', '>=', $date)
            ->count();
    }

    /**
     * @return array<int, int>
     */
    private function lastSevenDaysChart(): array
    {
        return collect(range(6, 0))
            ->map(fn (int $daysAgo): int => $this->clicks()
                ->whereDate('clicked_at', now()->subDays($daysAgo)->toDateString())
                ->count())
            ->all();
    }

    private function clicks(): Builder
    {
        $siteId = $this->selectedSiteId();

        return Click::query()
            ->when($siteId, fn (Builder $query) => $query->where('site_id', $siteId));
    }

    private function selectedSite(): ?Site
    {
        $siteId = $this->selectedSiteId();

        if (! $siteId) {
            return null;
        }

        return Site::query()->find($siteId);
    }

    private function selectedSiteId(): ?int
    {
        $siteId = $this->pageFilters['site_id'] ?? null;

        return filled($siteId) ? (int) $siteId : null;
    }
}
