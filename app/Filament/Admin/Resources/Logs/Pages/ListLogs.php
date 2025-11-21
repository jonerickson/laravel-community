<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Logs\Pages;

use App\Filament\Admin\Resources\Logs\Actions\PurgeAction;
use App\Filament\Admin\Resources\Logs\LogResource;
use Filament\Resources\Pages\ListRecords;

class ListLogs extends ListRecords
{
    protected static string $resource = LogResource::class;

    protected ?string $subheading = 'Pertinent integration logs for the platform.';

    protected function getHeaderActions(): array
    {
        return [
            PurgeAction::make(),
        ];
    }
}
