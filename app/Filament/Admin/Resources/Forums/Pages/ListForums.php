<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Forums\Pages;

use App\Filament\Admin\Resources\Forums\ForumResource;
use App\Filament\Imports\ForumImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListForums extends ListRecords
{
    protected static string $resource = ForumResource::class;

    protected ?string $subheading = 'Manage your community forums and discussion.';

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(ForumImporter::class),
            CreateAction::make(),
        ];
    }
}
