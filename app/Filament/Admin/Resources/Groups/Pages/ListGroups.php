<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Groups\Pages;

use App\Filament\Admin\Resources\Groups\GroupResource;
use App\Filament\Imports\GroupImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListGroups extends ListRecords
{
    protected static string $resource = GroupResource::class;

    protected ?string $subheading = 'Manage your community groups.';

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(GroupImporter::class),
            CreateAction::make(),
        ];
    }
}
