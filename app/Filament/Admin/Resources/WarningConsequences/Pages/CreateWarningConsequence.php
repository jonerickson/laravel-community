<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\WarningConsequences\Pages;

use App\Filament\Admin\Resources\WarningConsequences\WarningConsequenceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWarningConsequence extends CreateRecord
{
    protected static string $resource = WarningConsequenceResource::class;
}
