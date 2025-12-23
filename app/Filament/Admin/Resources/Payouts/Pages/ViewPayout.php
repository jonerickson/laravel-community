<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Payouts\Pages;

use App\Filament\Admin\Resources\Payouts\PayoutResource;
use App\Filament\Admin\Resources\Payouts\RelationManagers\CommissionsRelationManager;
use Filament\Resources\Pages\ViewRecord;

class ViewPayout extends ViewRecord
{
    protected static string $resource = PayoutResource::class;

    public function getRelationManagers(): array
    {
        return [
            CommissionsRelationManager::make(),
        ];
    }
}
