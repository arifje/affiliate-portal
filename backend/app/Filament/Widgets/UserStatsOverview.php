<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 5;

    protected ?string $pollingInterval = '30s';

    protected function getHeading(): ?string
    {
        return __('admin.widgets.users.heading');
    }

    protected function getDescription(): ?string
    {
        return __('admin.widgets.users.description');
    }

    protected function getStats(): array
    {
        $totalUsers = User::query()->count();
        $activeUsers = User::query()->where('is_active', true)->count();
        $inactiveUsers = $totalUsers - $activeUsers;
        $verifiedUsers = User::query()->whereNotNull('email_verified_at')->count();
        $recentUsers = User::query()->where('created_at', '>=', now()->subDays(29)->startOfDay())->count();

        return [
            Stat::make(__('admin.widgets.users.total'), number_format($totalUsers))
                ->description(__('admin.widgets.common.active_inactive', [
                    'active' => number_format($activeUsers),
                    'inactive' => number_format($inactiveUsers),
                ]))
                ->icon(Heroicon::OutlinedUsers)
                ->url(UserResource::getUrl())
                ->color('primary'),
            Stat::make(__('admin.widgets.users.active'), number_format($activeUsers))
                ->description($totalUsers > 0 ? __('admin.widgets.users.user_percentage', ['percentage' => round(($activeUsers / $totalUsers) * 100)]) : __('admin.widgets.users.empty'))
                ->icon(Heroicon::OutlinedShieldCheck)
                ->url(UserResource::getUrl())
                ->color('success'),
            Stat::make(__('admin.widgets.users.verified'), number_format($verifiedUsers))
                ->description(__('admin.widgets.users.created_30d', ['count' => number_format($recentUsers)]))
                ->icon(Heroicon::OutlinedEnvelope)
                ->url(UserResource::getUrl())
                ->color('info'),
        ];
    }
}
