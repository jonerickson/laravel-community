<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;
use Override;

class OrderStatsOverview extends StatsOverviewWidget
{
    #[Override]
    protected function getStats(): array
    {
        $totalOrders = $this->calculateTotalOrders();
        $ordersToday = $this->calculateOrdersToday();
        $averageOrderValue = $this->calculateAverageOrderValue();
        $refundRate = $this->calculateRefundRate();

        return [
            Stat::make('Total Orders', Number::format($totalOrders))
                ->description('All successful orders')
                ->icon(Heroicon::OutlinedShoppingCart)
                ->color('primary'),

            Stat::make('Orders Today', Number::format($ordersToday))
                ->description('Completed today')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->color('success'),

            Stat::make('Avg. Order Value', Number::currency($averageOrderValue))
                ->description('Average per order')
                ->icon(Heroicon::OutlinedCurrencyDollar)
                ->color('info'),

            Stat::make('Refund Rate', Number::percentage($refundRate, 1))
                ->description('Percentage of refunds')
                ->icon(Heroicon::OutlinedReceiptRefund)
                ->color($refundRate > 5 ? 'warning' : 'success'),
        ];
    }

    protected function calculateTotalOrders(): int
    {
        return Order::where('status', OrderStatus::Succeeded)->count();
    }

    protected function calculateOrdersToday(): int
    {
        return Order::where('status', OrderStatus::Succeeded)
            ->whereDate('created_at', today())
            ->count();
    }

    protected function calculateAverageOrderValue(): float
    {
        return Order::where('status', OrderStatus::Succeeded)
            ->where('amount_paid', '>', 0)
            ->average('amount_paid') / 100;
    }

    protected function calculateRefundRate(): float
    {
        $totalOrders = Order::whereIn('status', [OrderStatus::Succeeded, OrderStatus::Refunded])->count();

        if ($totalOrders === 0) {
            return 0;
        }

        $refundedOrders = Order::where('status', OrderStatus::Refunded)->count();

        return ($refundedOrders / $totalOrders) * 100;
    }
}
