<?php

declare(strict_types=1);

namespace App\Filament\Marketplace\Widgets;

use App\Enums\PayoutStatus;
use App\Models\Payout;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;
use Override;

class PayoutStatsOverview extends StatsOverviewWidget
{
    #[Override]
    protected function getStats(): array
    {
        return [
            Stat::make('Payouts MTD', Number::currency($this->calculateMtd()))
                ->description('Payouts earned this month')
                ->icon(Heroicon::OutlinedCurrencyDollar),

            Stat::make('Payouts QTD', Number::currency($this->calculateQtd()))
                ->description('Payouts earned this quarter')
                ->icon(Heroicon::OutlinedCurrencyDollar),

            Stat::make('Payouts YTD', Number::currency($this->calculateYtd()))
                ->description('Payouts earned this year')
                ->icon(Heroicon::OutlinedCurrencyDollar),

            Stat::make('Lifetime Payouts', Number::currency($this->calculateLifetime()))
                ->description('Lifetime payout earnings')
                ->icon(Heroicon::OutlinedCurrencyDollar),
        ];
    }

    protected function calculateMtd(): float
    {
        return (float) Payout::whereBelongsTo(Auth::user(), 'seller')
            ->where('status', PayoutStatus::Completed)
            ->whereBetween('created_at', [today()->startOfMonth(), today()->endOfMonth()])
            ->sum('amount') / 100;
    }

    protected function calculateQtd(): float
    {
        return (float) Payout::whereBelongsTo(Auth::user(), 'seller')
            ->where('status', PayoutStatus::Completed)
            ->whereBetween('created_at', [today()->startOfQuarter(), today()->endOfQuarter()])
            ->sum('amount') / 100;
    }

    protected function calculateYtd(): float
    {
        return (float) Payout::whereBelongsTo(Auth::user(), 'seller')
            ->where('status', PayoutStatus::Completed)
            ->whereBetween('created_at', [today()->startOfYear(), today()->endOfYear()])
            ->sum('amount') / 100;
    }

    protected function calculateLifetime(): float
    {
        return (float) Payout::whereBelongsTo(Auth::user(), 'seller')
            ->where('status', PayoutStatus::Completed)
            ->sum('amount') / 100;
    }
}
