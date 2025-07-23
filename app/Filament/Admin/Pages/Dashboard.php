<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected ?string $heading = 'Administration';

    protected ?string $subheading = 'Welcome to the Mountain Interactive Admin Control Panel. From here you can manage your entire application and perform essential administrative functions.';
}
