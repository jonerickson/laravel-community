<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Forums\Pages;

use App\Filament\Admin\Resources\Forums\ForumResource;
use Filament\Resources\Pages\CreateRecord;
use Override;

class CreateForum extends CreateRecord
{
    protected static string $resource = ForumResource::class;

    #[Override]
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
