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

    protected ?string $pollingInterval = '30s';

    protected function getHeading(): ?string
    {
        return __('admin.widgets.visitors.heading');
    }

    protected function getDescription(): ?string
    {
        $site = $this->selectedSite();

        return $site
            ? __('admin.widgets.visitors.site_description', ['site' => $site->name])
            : __('admin.widgets.visitors.description');
    }

    protected function getStats(): array
    {
        $now = $this->siteNow();

        return [
            Stat::make(__('admin.widgets.visitors.online'), number_format($this->visitorsOnline()))
                ->description(__('admin.widgets.visitors.online_description'))
                ->icon(Heroicon::OutlinedSignal)
                ->color('success'),
            Stat::make(__('admin.widgets.common.today'), number_format($this->uniqueVisitorsOn($now)))
                ->description(__('admin.widgets.visitors.today_description'))
                ->icon(Heroicon::OutlinedUserGroup)
                ->chart($this->lastSevenDaysChart())
                ->color('primary'),
            Stat::make(__('admin.widgets.common.this_week'), number_format($this->uniqueVisitorsSince($now->copy()->startOfWeek())))
                ->description(__('admin.widgets.visitors.week_description'))
                ->icon(Heroicon::OutlinedCalendarDays)
                ->color('info'),
            Stat::make(__('admin.widgets.common.this_month'), number_format($this->uniqueVisitorsSince($now->copy()->startOfMonth())))
                ->description(__('admin.widgets.visitors.month_description'))
                ->icon(Heroicon::OutlinedChartBar)
                ->color('gray'),
            Stat::make(__('admin.widgets.common.this_year'), number_format($this->uniqueVisitorsSince($now->copy()->startOfYear())))
                ->description(__('admin.widgets.visitors.year_description'))
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
