<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PolicyResource\Pages;

use App\Filament\Admin\Resources\PolicyResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePolicy extends CreateRecord
{
    protected static string $resource = PolicyResource::class;
}
