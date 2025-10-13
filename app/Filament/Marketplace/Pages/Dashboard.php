<?php

declare(strict_types=1);

namespace App\Filament\Marketplace\Pages;

use App\Filament\Marketplace\Widgets\CommissionStatsOverview;
use App\Filament\Marketplace\Widgets\MarketplaceSalesTable;
use Filament\Pages\Dashboard as BaseDashboard;
use Override;

class Dashboard extends BaseDashboard
{
    protected ?string $heading = 'Marketplace';

    protected ?string $subheading = 'Welcome to the Mountain Interactive marketplace. From here you can manage your products, payouts and customers.';

    #[Override]
    public function getWidgets(): array
    {
        return [
            CommissionStatsOverview::class,
            MarketplaceSalesTable::class,
        ];
    }
}
