<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Override;

class UsersAnalyticsChart extends ChartWidget
{
    protected ?string $heading = 'User Analytics';

    protected ?string $description = 'A comprehensive overview of user activity in your community.';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    protected ?string $maxHeight = '400px';

    #[Override]
    protected function getData(): array
    {
        $data = $this->getUserMetrics();

        return [
            'datasets' => [
                [
                    'label' => 'New Registrations',
                    'data' => $data->pluck('registrations')->toArray(),
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Active Users',
                    'data' => $data->pluck('active')->toArray(),
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Email Verifications',
                    'data' => $data->pluck('verifications')->toArray(),
                    'borderColor' => 'rgb(168, 85, 247)',
                    'backgroundColor' => 'rgba(168, 85, 247, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Total Users',
                    'data' => $data->pluck('cumulative')->toArray(),
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
                        'text' => 'Daily Count',
                    ],
                ],
                'cumulative' => [
                    'beginAtZero' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Total Users',
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

    protected function getUserMetrics(): Collection
    {
        $now = Carbon::now();
        $startDate = $now->copy()->subDays(30);

        $registrations = DB::table('users')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $activeUsers = DB::table('users')
            ->select(DB::raw('DATE(last_seen_at) as date'), DB::raw('count(*) as count'))
            ->where('last_seen_at', '>=', $startDate)
            ->whereNotNull('last_seen_at')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $verifications = DB::table('users')
            ->select(DB::raw('DATE(email_verified_at) as date'), DB::raw('count(*) as count'))
            ->where('email_verified_at', '>=', $startDate)
            ->whereNotNull('email_verified_at')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $initialUserCount = DB::table('users')
            ->where('created_at', '<', $startDate)
            ->count();

        $allDates = collect();
        $cumulativeTotal = $initialUserCount;

        for ($date = $startDate->copy(); $date->lte($now); $date->addDay()) {
            $dateKey = $date->format('Y-m-d');
            $registrationsCount = (int) $registrations->get($dateKey, 0);
            $cumulativeTotal += $registrationsCount;

            $allDates->push([
                'date' => $date->format('M d'),
                'registrations' => $registrationsCount,
                'active' => (int) $activeUsers->get($dateKey, 0),
                'verifications' => (int) $verifications->get($dateKey, 0),
                'cumulative' => $cumulativeTotal,
            ]);
        }

        return $allDates;
    }
}
