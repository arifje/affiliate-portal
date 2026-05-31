<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Models\ProductView;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class ProductStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '30s';

    protected function getHeading(): ?string
    {
        return __('admin.widgets.products.heading');
    }

    protected function getDescription(): ?string
    {
        return __('admin.widgets.products.description');
    }

    protected function getStats(): array
    {
        $totalProducts = Product::query()->count();
        $activeProducts = Product::query()->where('is_active', true)->count();
        $inactiveProducts = $totalProducts - $activeProducts;
        $totalUniqueViews = ProductView::query()->count();
        $totalRawViews = ProductView::query()->sum('view_count');

        return [
            Stat::make(__('admin.widgets.products.total'), number_format($totalProducts))
                ->description(__('admin.widgets.common.active_inactive', [
                    'active' => number_format($activeProducts),
                    'inactive' => number_format($inactiveProducts),
                ]))
                ->icon(Heroicon::OutlinedShoppingBag)
                ->url(ProductResource::getUrl())
                ->color('primary'),
            Stat::make(__('admin.widgets.products.active'), number_format($activeProducts))
                ->description($totalProducts > 0 ? __('admin.widgets.products.catalog_percentage', ['percentage' => round(($activeProducts / $totalProducts) * 100)]) : __('admin.widgets.products.empty'))
                ->icon(Heroicon::OutlinedCheckCircle)
                ->url(ProductResource::getUrl())
                ->color('success'),
            Stat::make(__('admin.widgets.products.unique_views'), number_format($totalUniqueViews))
                ->description($this->viewDescription())
                ->icon(Heroicon::OutlinedEye)
                ->chart($this->lastSevenDaysChart())
                ->color('info'),
            Stat::make(__('admin.widgets.products.raw_hits'), number_format($totalRawViews))
                ->description(__('admin.widgets.products.raw_hits_description'))
                ->icon(Heroicon::OutlinedChartBar)
                ->color('gray'),
        ];
    }

    private function viewDescription(): string
    {
        return implode(' | ', [
            __('admin.widgets.common.today_count', ['count' => number_format($this->uniqueViewsSince(now()->startOfDay()))]),
            number_format($this->uniqueViewsSince(now()->subDays(6)->startOfDay())).' 7d',
            number_format($this->uniqueViewsSince(now()->subDays(29)->startOfDay())).' 30d',
        ]);
    }

    private function uniqueViewsSince(Carbon $date): int
    {
        return ProductView::query()
            ->whereDate('viewed_on', '>=', $date->toDateString())
            ->count();
    }

    /**
     * @return array<int, int>
     */
    private function lastSevenDaysChart(): array
    {
        return collect(range(6, 0))
            ->map(fn (int $daysAgo): int => ProductView::query()
                ->whereDate('viewed_on', now()->subDays($daysAgo)->toDateString())
                ->count())
            ->all();
    }
}
