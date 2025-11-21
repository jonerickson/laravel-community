<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ActivityLogs\Pages;

use App\Filament\Admin\Resources\ActivityLogs\Actions\PurgeAction;
use App\Filament\Admin\Resources\ActivityLogs\ActivityLogResource;
use Filament\Resources\Pages\ListRecords;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;

    protected ?string $subheading = 'Your chronological history of every important event that took place.';

    protected function getHeaderActions(): array
    {
        return [
            PurgeAction::make(),
        ];
    }
}
