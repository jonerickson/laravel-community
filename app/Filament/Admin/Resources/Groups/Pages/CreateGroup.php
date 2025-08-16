<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Groups\Pages;

use App\Filament\Admin\Resources\Groups\GroupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGroup extends CreateRecord
{
    protected static string $resource = GroupResource::class;
}
