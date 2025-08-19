<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Reports\Pages;

use App\Filament\Admin\Resources\Reports\ReportResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReport extends CreateRecord
{
    protected static string $resource = ReportResource::class;
}
