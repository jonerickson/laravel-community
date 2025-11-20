<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Webhooks\Widgets;

use App\Models\Log;
use App\Models\Webhook;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Override;

class WebhookLogActivity extends ChartWidget
{
    protected ?string $heading = 'Live Webhook Requests';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '350px';

    protected ?string $pollingInterval = '10s';

    protected ?array $options = [
        'plugins' => [
            'legend' => [
                'display' => false,
            ],
        ],
    ];

    #[Override]
    protected function getData(): array
    {
        $data = Log::query()
            ->whereHasMorph('loggable', [Webhook::class])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        return [
            'datasets' => [
                [
                    'label' => 'Webhooks Sent',
                    'data' => $data,
                    'fill' => true,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
