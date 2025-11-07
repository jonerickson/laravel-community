<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Fields\Pages;

use App\Filament\Admin\Resources\Fields\FieldResource;
use Filament\Resources\Pages\CreateRecord;

class CreateField extends CreateRecord
{
    protected static string $resource = FieldResource::class;
}
