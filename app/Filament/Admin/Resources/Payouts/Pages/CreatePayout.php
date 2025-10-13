<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Payouts\Pages;

use App\Filament\Admin\Resources\Payouts\PayoutResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePayout extends CreateRecord
{
    protected static string $resource = PayoutResource::class;
}
