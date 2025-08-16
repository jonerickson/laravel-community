<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Policies\Pages;

use App\Filament\Admin\Resources\Policies\PolicyResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePolicy extends CreateRecord
{
    protected static string $resource = PolicyResource::class;
}
