<?php

declare(strict_types=1);

namespace App\Filament\Marketplace\Widgets;

use App\Enums\ProductApprovalStatus;
use App\Models\Commission;
use App\Models\Product;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;
use Override;

class CommissionStatsOverview extends StatsOverviewWidget
{
    #[Override]
    protected function getStats(): array
    {
        $totalProducts = $this->calculateTotalProducts();
        $approvedProducts = $this->calculateApprovedProducts();
        $totalSales = $this->calculateTotalSales();
        $totalCommission = $this->calculateTotalCommission();

        return [
            Stat::make('Total Products', Number::format($totalProducts))
                ->description('Products you have created')
                ->icon(Heroicon::OutlinedShoppingBag)
                ->color('primary'),

            Stat::make('Approved Products', Number::format($approvedProducts))
                ->description('Available in the store')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success'),

            Stat::make('Total Sales', Number::format($totalSales))
                ->description('Orders with your products')
                ->icon(Heroicon::OutlinedShoppingCart)
                ->color('info'),

            Stat::make('Total Commission', Number::currency($totalCommission))
                ->description('Commission earned')
                ->icon(Heroicon::OutlinedCurrencyDollar)
                ->color('success'),
        ];
    }

    protected function calculateTotalProducts(): int
    {
        return Product::whereBelongsTo(Auth::user(), 'seller')
            ->count();
    }

    protected function calculateApprovedProducts(): int
    {
        return Product::whereBelongsTo(Auth::user(), 'seller')
            ->where('approval_status', ProductApprovalStatus::Approved)
            ->count();
    }

    protected function calculateTotalSales(): int
    {
        return Commission::whereBelongsTo(Auth::user(), 'seller')
            ->count();
    }

    protected function calculateTotalCommission(): float
    {
        return (float) Commission::whereBelongsTo(Auth::user(), 'seller')
            ->sum('amount') / 100;
    }
}
