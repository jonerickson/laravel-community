<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Whitelists\Pages;

use App\Filament\Admin\Resources\Whitelists\WhitelistResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWhitelist extends CreateRecord
{
    protected static string $resource = WhitelistResource::class;
}
