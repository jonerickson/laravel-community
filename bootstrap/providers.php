<?php

declare(strict_types=1);

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\ContextServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\Filament\MarketplacePanelProvider::class,
    App\Providers\HorizonServiceProvider::class,
    App\Providers\MacroServiceProvider::class,
    App\Providers\MailboxProvider::class,
    App\Providers\MigrationServiceProvider::class,
    App\Providers\PaymentServiceProvider::class,
    App\Providers\PayoutServiceProvider::class,
    App\Providers\SupportTicketServiceProvider::class,
    App\Providers\TelescopeServiceProvider::class,
];
