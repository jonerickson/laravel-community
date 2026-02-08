<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Disputes\Widgets;

use App\Enums\DisputeStatus;
use App\Models\Dispute;
use Filament\Widgets\ChartWidget;
use Override;

class DisputeOutcomeChart extends ChartWidget
{
    protected ?string $heading = 'Dispute outcomes';

    protected ?string $description = 'Breakdown of dispute results.';

    protected static ?int $sort = 3;

    protected ?string $maxHeight = '300px';

    #[Override]
    protected function getData(): array
    {
        $won = Dispute::where('status', DisputeStatus::Won)->count();
        $lost = Dispute::where('status', DisputeStatus::Lost)->count();
        $open = Dispute::whereIn('status', [
            DisputeStatus::NeedsResponse,
            DisputeStatus::WarningNeedsResponse,
            DisputeStatus::UnderReview,
            DisputeStatus::WarningUnderReview,
        ])->count();

        return [
            'datasets' => [
                [
                    'data' => [$won, $lost, $open],
                    'backgroundColor' => [
                        'rgb(34, 197, 94)',
                        'rgb(239, 68, 68)',
                        'rgb(251, 191, 36)',
                    ],
                ],
            ],
            'labels' => ['Won', 'Lost', 'Open'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    #[Override]
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
