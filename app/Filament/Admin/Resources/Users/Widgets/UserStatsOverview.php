<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Widgets;

use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;
use Override;

class UserStatsOverview extends StatsOverviewWidget
{
    #[Override]
    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', Number::format(User::count()))
                ->description('Total community users')
                ->icon(Heroicon::OutlinedUserGroup),
            Stat::make('New Today', Number::format(User::whereDate('created_at', today())->count()))
                ->description('New registrations today')
                ->icon(Heroicon::OutlinedCalendarDays),
            Stat::make('Monthly Active', Number::format(User::where('last_seen_at', '>=', now()->subMonth())->count()))
                ->description('Logged in this month')
                ->icon(Heroicon::OutlinedCursorArrowRipple),
            Stat::make('User Churn', Number::percentage($this->calculateChurn()))
                ->description('Over 30 days')
                ->icon(Heroicon::OutlinedArrowPath),
        ];
    }

    protected function calculateChurn(): float
    {
        $activeLastMonth = User::where('last_seen_at', '>=', now()->subMonth()->startOfMonth())
            ->where('last_seen_at', '<', now()->startOfMonth())
            ->count();

        if ($activeLastMonth === 0) {
            return 0;
        }

        $activeThisMonth = User::where('last_seen_at', '>=', now()->startOfMonth())->count();

        $churned = $activeLastMonth - $activeThisMonth;

        return round(($churned / $activeLastMonth) * 100, 1);
    }
}
