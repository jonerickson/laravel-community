<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Disputes\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Override;

class DisputeFrequencyChart extends ChartWidget
{
    protected ?string $heading = 'Dispute Frequency';

    protected ?string $description = 'Disputes opened per month over the past 12 months.';

    protected static ?int $sort = 2;

    protected ?string $maxHeight = '300px';

    protected int|string|array $columnSpan = 'full';

    #[Override]
    protected function getData(): array
    {
        $data = $this->getMonthlyDisputes();

        return [
            'datasets' => [
                [
                    'label' => 'Disputes',
                    'data' => $data->pluck('count')->toArray(),
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.8)',
                ],
            ],
            'labels' => $data->pluck('month')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    #[Override]
    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
        ];
    }

    protected function getMonthlyDisputes(): Collection
    {
        $startDate = Carbon::now()->subMonths(11)->startOfMonth();

        $disputes = DB::table('disputes')
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as count'),
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->keyBy(fn ($row): string => $row->year.'-'.str_pad((string) $row->month, 2, '0', STR_PAD_LEFT));

        $result = collect();

        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $key = $date->format('Y-m');

            $result->push([
                'month' => $date->format('M Y'),
                'count' => (int) ($disputes->get($key)?->count ?? 0),
            ]);
        }

        return $result;
    }
}
