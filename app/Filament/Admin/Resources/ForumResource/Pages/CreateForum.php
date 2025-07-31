<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ForumResource\Pages;

use App\Filament\Admin\Resources\ForumResource;
use Filament\Resources\Pages\CreateRecord;

class CreateForum extends CreateRecord
{
    protected static string $resource = ForumResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
