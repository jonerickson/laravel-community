<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Subscription;
use Exception;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
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
        $orders = Order::where('status', OrderStatus::Succeeded)
            ->with(['items.price'])
            ->get();

        return $orders->sum(fn (Order $order) => $order->items->sum(fn ($item) => $item->amount));
    }

    protected function calculateRevenueThisMonth(): float
    {
        $orders = Order::where('status', OrderStatus::Succeeded)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->with(['items.price'])
            ->get();

        return $orders->sum(fn (Order $order) => $order->items->sum(fn ($item) => $item->amount));
    }

    protected function calculateMRR(): float
    {
        $activeSubscriptions = Subscription::where('stripe_status', 'active')
            ->orWhere('stripe_status', 'trialing')
            ->get();

        $mrr = 0;

        foreach ($activeSubscriptions as $subscription) {
            try {
                $stripeSubscription = $subscription->asStripeSubscription();

                foreach ($stripeSubscription->items->data as $item) {
                    $amount = ($item->price->unit_amount / 100) ?? 0;
                    $interval = $item->price->recurring->interval ?? 'month';

                    if ($interval === 'year') {
                        $mrr += $amount / 12;
                    } elseif ($interval === 'month') {
                        $mrr += $amount;
                    }
                }
            } catch (Exception) {
                continue;
            }
        }

        return $mrr;
    }
}
