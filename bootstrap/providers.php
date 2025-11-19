<?php

declare(strict_types=1);

$providers = [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\Filament\MarketplacePanelProvider::class,
    App\Providers\MacroServiceProvider::class,
    App\Providers\MigrationServiceProvider::class,
    App\Providers\PaymentServiceProvider::class,
    App\Providers\SupportTicketServiceProvider::class,
];

if (class_exists('\\Laravel\\Horizon\\Horizon')) {
    $providers[] = App\Providers\HorizonServiceProvider::class;
}

if (class_exists('\\Laravel\\Telescope\\Telescope')) {
    $providers[] = App\Providers\TelescopeServiceProvider::class;
}

return $providers;
