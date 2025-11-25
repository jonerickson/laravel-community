<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Subscription;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;
use Override;

class RevenueStatsOverview extends StatsOverviewWidget
{
    #[Override]
    protected function getStats(): array
    {
        $totalRevenue = $this->calculateTotalRevenue();
        $revenueThisMonth = $this->calculateRevenueThisMonth();
        $mrr = $this->calculateMRR();
        $arr = $mrr * 12;

        return [
            Stat::make('Total Revenue', Number::currency($totalRevenue))
                ->description('All-time revenue')
                ->icon(Heroicon::OutlinedCurrencyDollar)
                ->color('success'),

            Stat::make('Revenue This Month', Number::currency($revenueThisMonth))
                ->description('Revenue in '.now()->format('F'))
                ->icon(Heroicon::OutlinedCalendarDays)
                ->color('primary'),

            Stat::make('MRR', Number::currency($mrr))
                ->description('Monthly recurring revenue')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('info'),

            Stat::make('ARR', Number::currency($arr))
                ->description('Annual recurring revenue')
                ->icon(Heroicon::OutlinedChartBar)
                ->color('success'),
        ];
    }

    protected function calculateTotalRevenue(): float
    {
        return Order::where('status', OrderStatus::Succeeded)
            ->sum('amount_paid') / 100;
    }

    protected function calculateRevenueThisMonth(): float
    {
        return Order::where('status', OrderStatus::Succeeded)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount_paid') / 100;
    }

    protected function calculateMRR(): float
    {
        $activeSubscriptions = Subscription::query()
            ->orWhere(fn (Builder $query) => $query->active())
            ->orWhere(fn (Builder $query) => $query->onTrial())
            ->orWhere(fn (Builder $query) => $query->onGracePeriod())
            ->with('price')
            ->get();

        $mrr = 0;

        foreach ($activeSubscriptions as $subscription) {
            if (! $subscription->price) {
                continue;
            }

            $amount = $subscription->price->amount;
            $interval = $subscription->price->interval?->value;

            if ($interval === 'year') {
                $mrr += $amount / 12;
            } elseif ($interval === 'month') {
                $mrr += $amount;
            }
        }

        return $mrr;
    }
}
