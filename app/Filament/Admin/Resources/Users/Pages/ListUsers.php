<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\Admin\Resources\Users\Widgets\UserStatsOverview;
use App\Filament\Imports\UserImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected ?string $subheading = 'Manage the users and their profile information.';

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(UserImporter::class),
            CreateAction::make(),
        ];
    }

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            UserStatsOverview::make(),
        ];
    }
}
