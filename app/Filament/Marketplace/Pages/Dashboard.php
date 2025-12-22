<?php

declare(strict_types=1);

namespace App\Filament\Marketplace\Pages;

use App\Filament\Marketplace\Widgets\CommissionStatsOverview;
use App\Filament\Marketplace\Widgets\MarketplaceSalesTable;
use App\Filament\Marketplace\Widgets\PayoutSetupWidget;
use App\Filament\Marketplace\Widgets\PayoutStatsOverview;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;
use Override;

class Dashboard extends BaseDashboard
{
    protected ?string $heading = 'Marketplace';

    #[Override]
    public function getWidgets(): array
    {
        return [
            PayoutSetupWidget::class,
            CommissionStatsOverview::class,
            PayoutStatsOverview::class,
            MarketplaceSalesTable::class,
        ];
    }

    #[Override]
    public function getSubheading(): string|Htmlable|null
    {
        return sprintf('Welcome to the %s marketplace. From here you can manage your products, payouts and customers.', config('app.name'));
    }
}
