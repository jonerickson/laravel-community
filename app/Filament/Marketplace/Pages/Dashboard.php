<?php

declare(strict_types=1);

namespace App\Filament\Marketplace\Pages;

use App\Filament\Marketplace\Widgets\CommissionStatsOverview;
use App\Filament\Marketplace\Widgets\MarketplaceSalesTable;
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
            CommissionStatsOverview::class,
            MarketplaceSalesTable::class,
        ];
    }

    public function getSubheading(): string|Htmlable|null
    {
        $name = config('app.name');

        return "Welcome to the $name marketplace. From here you can manage your products, payouts and customers.";
    }
}
