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

    protected ?string $heading = 'Products';

    protected ?string $description = 'Catalog size and unique product-page views.';

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $totalProducts = Product::query()->count();
        $activeProducts = Product::query()->where('is_active', true)->count();
        $inactiveProducts = $totalProducts - $activeProducts;
        $totalUniqueViews = ProductView::query()->count();
        $totalRawViews = ProductView::query()->sum('view_count');

        return [
            Stat::make('Total products', number_format($totalProducts))
                ->description(number_format($activeProducts).' active, '.number_format($inactiveProducts).' inactive')
                ->icon(Heroicon::OutlinedShoppingBag)
                ->url(ProductResource::getUrl())
                ->color('primary'),
            Stat::make('Active products', number_format($activeProducts))
                ->description($totalProducts > 0 ? round(($activeProducts / $totalProducts) * 100).'% of catalog' : 'No products yet')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->url(ProductResource::getUrl())
                ->color('success'),
            Stat::make('Unique product views', number_format($totalUniqueViews))
                ->description($this->viewDescription())
                ->icon(Heroicon::OutlinedEye)
                ->chart($this->lastSevenDaysChart())
                ->color('info'),
            Stat::make('Raw product hits', number_format($totalRawViews))
                ->description('Repeat refreshes included')
                ->icon(Heroicon::OutlinedChartBar)
                ->color('gray'),
        ];
    }

    private function viewDescription(): string
    {
        return implode(' | ', [
            number_format($this->uniqueViewsSince(now()->startOfDay())).' today',
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
