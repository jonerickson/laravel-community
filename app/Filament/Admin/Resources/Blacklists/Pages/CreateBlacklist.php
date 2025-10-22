<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Blacklists\Pages;

use App\Filament\Admin\Resources\Blacklists\BlacklistResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBlacklist extends CreateRecord
{
    protected static string $resource = BlacklistResource::class;
}
