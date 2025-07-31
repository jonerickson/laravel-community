<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ApiTokenResource\Pages;

use App\Filament\Admin\Resources\ApiTokenResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListApiTokens extends ListRecords
{
    protected static string $resource = ApiTokenResource::class;

    protected ?string $subheading = 'Manage your system API keys that allow programatic access to your app data.';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
