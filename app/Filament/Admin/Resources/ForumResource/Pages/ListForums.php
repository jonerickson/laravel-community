<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ForumResource\Pages;

use App\Filament\Admin\Resources\ForumResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListForums extends ListRecords
{
    protected static string $resource = ForumResource::class;

    protected ?string $subheading = 'Manage your community forums and discussion.';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
