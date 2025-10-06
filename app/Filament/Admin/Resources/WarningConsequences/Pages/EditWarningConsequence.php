<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\WarningConsequences\Pages;

use App\Filament\Admin\Resources\WarningConsequences\WarningConsequenceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWarningConsequence extends EditRecord
{
    protected static string $resource = WarningConsequenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
