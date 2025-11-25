<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\Widgets;

use App\Enums\OrderStatus;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Override;

class OrdersAnalyticsChart extends ChartWidget
{
    protected ?string $heading = 'Order Analytics';

    protected ?string $description = 'A comprehensive overview of recent order activity.';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    protected ?string $maxHeight = '400px';

    #[Override]
    protected function getData(): array
    {
        $data = $this->getOrderMetrics();

        return [
            'datasets' => [
                [
                    'label' => 'Orders',
                    'data' => $data->pluck('orders')->toArray(),
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Successful Orders',
                    'data' => $data->pluck('successful')->toArray(),
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Cancelled Orders',
                    'data' => $data->pluck('cancelled')->toArray(),
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Refunded Orders',
                    'data' => $data->pluck('refunded')->toArray(),
                    'borderColor' => 'rgb(251, 146, 60)',
                    'backgroundColor' => 'rgba(251, 146, 60, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Daily Revenue',
                    'data' => $data->pluck('revenue')->toArray(),
                    'borderColor' => 'rgb(168, 85, 247)',
                    'backgroundColor' => 'rgba(168, 85, 247, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                    'yAxisID' => 'revenue',
                ],
                [
                    'label' => 'Cumulative Revenue',
                    'data' => $data->pluck('cumulative_revenue')->toArray(),
                    'borderColor' => 'rgb(100, 116, 139)',
                    'backgroundColor' => 'rgba(100, 116, 139, 0.1)',
                    'fill' => false,
                    'tension' => 0.3,
                    'yAxisID' => 'cumulative',
                ],
            ],
            'labels' => $data->pluck('date')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    #[Override]
    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Order Count',
                    ],
                ],
                'revenue' => [
                    'beginAtZero' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Daily Revenue ($)',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
                'cumulative' => [
                    'beginAtZero' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Total Revenue ($)',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }

    protected function getOrderMetrics(): Collection
    {
        $now = Carbon::now();
        $startDate = $now->copy()->subDays(30);

        $orders = DB::table('orders')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $successfulOrders = DB::table('orders')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', $startDate)
            ->where('status', OrderStatus::Succeeded)
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $cancelledOrders = DB::table('orders')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', $startDate)
            ->where('status', OrderStatus::Cancelled)
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $refundedOrders = DB::table('orders')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', $startDate)
            ->where('status', OrderStatus::Refunded)
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $dailyRevenue = DB::table('orders')
            ->join('orders_items', 'orders.id', '=', 'orders_items.order_id')
            ->select(DB::raw('DATE(orders.created_at) as date'), DB::raw('sum(orders_items.amount) as revenue'))
            ->where('orders.created_at', '>=', $startDate)
            ->where('orders.status', OrderStatus::Succeeded)
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('revenue', 'date');

        // This is necessary because we want to sum using the `amount` computed
        // value, not the value from the DB column.
        /** @phpstan-ignore-next-line larastan.noUnnecessaryCollectionCall */
        $initialRevenue = DB::table('orders')
            ->join('orders_items', 'orders.id', '=', 'orders_items.order_id')
            ->where('orders.created_at', '<', $startDate)
            ->where('orders.status', OrderStatus::Succeeded)
            ->get()
            ->sum('orders_items.amount');

        $allDates = collect();
        $cumulativeRevenue = $initialRevenue;

        for ($date = $startDate->copy(); $date->lte($now); $date->addDay()) {
            $dateKey = $date->format('Y-m-d');
            $dayRevenue = ((int) $dailyRevenue->get($dateKey, 0)) / 100;
            $cumulativeRevenue += $dayRevenue;

            $allDates->push([
                'date' => $date->format('M d'),
                'orders' => (int) $orders->get($dateKey, 0),
                'successful' => (int) $successfulOrders->get($dateKey, 0),
                'cancelled' => (int) $cancelledOrders->get($dateKey, 0),
                'refunded' => (int) $refundedOrders->get($dateKey, 0),
                'revenue' => round($dayRevenue, 2),
                'cumulative_revenue' => round($cumulativeRevenue, 2),
            ]);
        }

        return $allDates;
    }
}
