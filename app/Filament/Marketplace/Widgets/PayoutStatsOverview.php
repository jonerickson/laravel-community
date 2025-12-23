<?php

declare(strict_types=1);

namespace App\Filament\Marketplace\Widgets;

use App\Data\BalanceData;
use App\Enums\PayoutStatus;
use App\Facades\PayoutProcessor;
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
    public static function canView(): bool
    {
        return Auth::user()->payouts_enabled;
    }

    #[Override]
    protected function getStats(): array
    {
        return [
            Stat::make('Payouts MTD', Number::currency($this->calculateMtd()))
                ->description('Payouts earned this month')
                ->icon(Heroicon::OutlinedCurrencyDollar),

            Stat::make('Payouts YTD', Number::currency($this->calculateYtd()))
                ->description('Payouts earned this year')
                ->icon(Heroicon::OutlinedCurrencyDollar),

            Stat::make('Lifetime Payouts', Number::currency($this->calculateLifetime()))
                ->description('Lifetime payout earnings')
                ->icon(Heroicon::OutlinedCurrencyDollar),

            Stat::make('Current Account Balance', Number::currency($this->getBalance()))
                ->description('Balance available for withdrawal')
                ->icon(Heroicon::OutlinedCurrencyDollar)
                ->color('success'),
        ];
    }

    protected function calculateMtd(): float
    {
        return (float) Payout::whereBelongsTo(Auth::user(), 'seller')
            ->where('status', PayoutStatus::Completed)
            ->whereBetween('created_at', [today()->startOfMonth(), today()->endOfMonth()])
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

    protected function getBalance(): float
    {
        $balance = PayoutProcessor::getBalance(Auth::user());

        if (! $balance instanceof BalanceData) {
            return 0;
        }

        return $balance->available;
    }
}
