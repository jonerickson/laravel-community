<?php

declare(strict_types=1);

namespace App\Filament\Marketplace\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected ?string $heading = 'Marketplace';

    protected ?string $subheading = 'Welcome to the Mountain Interactive marketplace. From here you can manage your products, payouts and customers.';
}
