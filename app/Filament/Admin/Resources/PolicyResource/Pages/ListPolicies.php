<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PolicyResource\Pages;

use App\Filament\Admin\Resources\PolicyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPolicies extends ListRecords
{
    protected static string $resource = PolicyResource::class;

    protected ?string $subheading = 'Manage your organization policies and guidelines.';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
