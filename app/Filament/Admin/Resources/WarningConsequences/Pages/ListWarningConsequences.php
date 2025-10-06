<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\WarningConsequences\Pages;

use App\Filament\Admin\Resources\WarningConsequences\WarningConsequenceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWarningConsequences extends ListRecords
{
    protected static string $resource = WarningConsequenceResource::class;

    protected ?string $subheading = 'Consequences define actions that will be taken when a user meets the specified warning points threshold.';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
