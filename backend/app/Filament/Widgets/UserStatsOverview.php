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

    protected ?string $heading = 'Users';

    protected ?string $description = 'Admin users and account status.';

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $totalUsers = User::query()->count();
        $activeUsers = User::query()->where('is_active', true)->count();
        $inactiveUsers = $totalUsers - $activeUsers;
        $verifiedUsers = User::query()->whereNotNull('email_verified_at')->count();
        $recentUsers = User::query()->where('created_at', '>=', now()->subDays(29)->startOfDay())->count();

        return [
            Stat::make('Total users', number_format($totalUsers))
                ->description(number_format($activeUsers).' active, '.number_format($inactiveUsers).' inactive')
                ->icon(Heroicon::OutlinedUsers)
                ->url(UserResource::getUrl())
                ->color('primary'),
            Stat::make('Active users', number_format($activeUsers))
                ->description($totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100).'% of users' : 'No users yet')
                ->icon(Heroicon::OutlinedShieldCheck)
                ->url(UserResource::getUrl())
                ->color('success'),
            Stat::make('Verified users', number_format($verifiedUsers))
                ->description(number_format($recentUsers).' created in 30d')
                ->icon(Heroicon::OutlinedEnvelope)
                ->url(UserResource::getUrl())
                ->color('info'),
        ];
    }
}
