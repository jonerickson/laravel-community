<?php

declare(strict_types=1);

namespace App\Filament\Admin\Clusters\Settings;

use App\Enums\Role;
use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class SettingsCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    public static function canAccess(): bool
    {
        return Auth::user()->hasRole(Role::Administrator);
    }
}
