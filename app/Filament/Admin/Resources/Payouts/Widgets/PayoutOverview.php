<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Payouts\Widgets;

use App\Enums\PayoutStatus;
use App\Facades\PayoutProcessor;
use App\Models\Payout;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;
use Override;

class PayoutOverview extends StatsOverviewWidget
{
    #[Override]
    protected function getStats(): array
    {
        $platformBalance = PayoutProcessor::getPlatformBalance();

        return [
            Stat::make('Platform Balance', Number::currency($platformBalance?->available ?? 0.0))
                ->description('Available balance in platform account')
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('success'),

            Stat::make('Payouts This Month', Number::currency($this->calculateMonthlyPayouts()))
                ->description('Total payouts sent this month')
                ->icon(Heroicon::OutlinedArrowTrendingUp)
                ->color('primary'),

            Stat::make('Pending Payouts', Number::currency($this->calculatePendingPayouts()))
                ->description($this->getPendingPayoutsCount().' payout(s) awaiting processing')
                ->icon(Heroicon::OutlinedClock)
                ->color('warning'),

            Stat::make('Failed This Month', $this->getFailedPayoutsCount())
                ->description('Payouts that failed this month')
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color('danger'),
        ];
    }

    protected function calculateMonthlyPayouts(): float
    {
        return (float) Payout::where('status', PayoutStatus::Completed)
            ->whereBetween('created_at', [today()->startOfMonth(), today()->endOfMonth()])
            ->sum('amount') / 100;
    }

    protected function calculatePendingPayouts(): float
    {
        return (float) Payout::where('status', PayoutStatus::Pending)
            ->sum('amount') / 100;
    }

    protected function getPendingPayoutsCount(): int
    {
        return Payout::where('status', PayoutStatus::Pending)->count();
    }

    protected function getFailedPayoutsCount(): int
    {
        return Payout::where('status', PayoutStatus::Failed)
            ->whereBetween('created_at', [today()->startOfMonth(), today()->endOfMonth()])
            ->count();
    }
}
