<?php

namespace App\Filament\Widgets;

use App\Models\Site;
use App\Models\SiteVisit;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class VisitorStatsOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 3;

    protected ?string $heading = 'Visitors';

    protected ?string $pollingInterval = '30s';

    protected function getDescription(): ?string
    {
        $site = $this->selectedSite();

        return $site
            ? 'Visitor activity for '.$site->name.'.'
            : 'Visitor activity across all sites.';
    }

    protected function getStats(): array
    {
        $now = $this->siteNow();

        return [
            Stat::make('Visitors online', number_format($this->visitorsOnline()))
                ->description('Active in the last 5 minutes')
                ->icon(Heroicon::OutlinedSignal)
                ->color('success'),
            Stat::make('Today', number_format($this->uniqueVisitorsOn($now)))
                ->description('Unique visitors today')
                ->icon(Heroicon::OutlinedUserGroup)
                ->chart($this->lastSevenDaysChart())
                ->color('primary'),
            Stat::make('This week', number_format($this->uniqueVisitorsSince($now->copy()->startOfWeek())))
                ->description('Unique visitors since Monday')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->color('info'),
            Stat::make('This month', number_format($this->uniqueVisitorsSince($now->copy()->startOfMonth())))
                ->description('Unique visitors this month')
                ->icon(Heroicon::OutlinedChartBar)
                ->color('gray'),
            Stat::make('This year', number_format($this->uniqueVisitorsSince($now->copy()->startOfYear())))
                ->description('Unique visitors this year')
                ->icon(Heroicon::OutlinedPresentationChartLine)
                ->color('gray'),
        ];
    }

    private function visitorsOnline(): int
    {
        return $this->visits()
            ->where('last_seen_at', '>=', now()->subMinutes(5))
            ->distinct()
            ->count('visitor_hash');
    }

    private function uniqueVisitorsOn(Carbon $date): int
    {
        return $this->visits()
            ->whereDate('visited_on', $date->toDateString())
            ->distinct()
            ->count('visitor_hash');
    }

    private function uniqueVisitorsSince(Carbon $date): int
    {
        return $this->visits()
            ->whereDate('visited_on', '>=', $date->toDateString())
            ->distinct()
            ->count('visitor_hash');
    }

    /**
     * @return array<int, int>
     */
    private function lastSevenDaysChart(): array
    {
        $now = $this->siteNow();

        return collect(range(6, 0))
            ->map(fn (int $daysAgo): int => $this->uniqueVisitorsOn($now->copy()->subDays($daysAgo)))
            ->all();
    }

    private function visits(): Builder
    {
        $siteId = $this->selectedSiteId();

        return SiteVisit::query()
            ->when($siteId, fn (Builder $query) => $query->where('site_id', $siteId));
    }

    private function siteNow(): Carbon
    {
        return now($this->selectedSite()?->timezone ?: config('app.timezone'));
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
