<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Warnings\Pages;

use App\Filament\Admin\Resources\Warnings\WarningResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWarning extends CreateRecord
{
    protected static string $resource = WarningResource::class;
}
