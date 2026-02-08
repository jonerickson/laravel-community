<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Disputes\Widgets;

use App\Enums\DisputeStatus;
use App\Models\Dispute;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;
use Override;

class DisputeStatsOverview extends StatsOverviewWidget
{
    #[Override]
    protected function getStats(): array
    {
        $totalDisputes = Dispute::count();
        $needingResponse = $this->calculateNeedingResponse();
        $totalAmount = $this->calculateTotalAmount();
        $winRate = $this->calculateWinRate();
        $avgResolutionDays = $this->calculateAverageResolutionDays();

        return [
            Stat::make('Total Disputes', Number::format($totalDisputes))
                ->description('All disputes')
                ->icon(Heroicon::OutlinedShieldExclamation)
                ->color('primary'),

            Stat::make('Needing Response', Number::format($needingResponse))
                ->description('Awaiting action')
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color($needingResponse > 0 ? 'danger' : 'success'),

            Stat::make('Total Amount', Number::currency($totalAmount))
                ->description('Total disputed amount')
                ->icon(Heroicon::OutlinedCurrencyDollar)
                ->color('warning'),

            Stat::make('Win Rate', Number::percentage($winRate, 1))
                ->description('Won vs lost disputes')
                ->icon(Heroicon::OutlinedTrophy)
                ->color($winRate >= 50 ? 'success' : 'danger'),

            Stat::make('Avg. Resolution', $avgResolutionDays !== null ? Number::format($avgResolutionDays, 1).' days' : 'N/A')
                ->description('Average days to resolve')
                ->icon(Heroicon::OutlinedClock)
                ->color('info'),
        ];
    }

    protected function calculateNeedingResponse(): int
    {
        return Dispute::whereIn('status', [
            DisputeStatus::NeedsResponse,
            DisputeStatus::WarningNeedsResponse,
        ])->count();
    }

    protected function calculateTotalAmount(): float
    {
        return Dispute::sum('amount') / 100;
    }

    protected function calculateWinRate(): float
    {
        $won = Dispute::where('status', DisputeStatus::Won)->count();
        $lost = Dispute::where('status', DisputeStatus::Lost)->count();

        if ($won + $lost === 0) {
            return 0;
        }

        return ($won / ($won + $lost)) * 100;
    }

    protected function calculateAverageResolutionDays(): ?float
    {
        $resolved = Dispute::whereIn('status', [DisputeStatus::Won, DisputeStatus::Lost])
            ->selectRaw('AVG(TIMESTAMPDIFF(DAY, created_at, updated_at)) as avg_days')
            ->value('avg_days');

        return $resolved !== null ? round((float) $resolved, 1) : null;
    }
}
