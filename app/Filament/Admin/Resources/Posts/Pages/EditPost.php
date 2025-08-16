<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Posts\Pages;

use App\Filament\Admin\Resources\Posts\PostResource;
use App\Models\Post;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->url(fn (Post $record) => $record->url, shouldOpenInNewTab: true),
            DeleteAction::make(),
        ];
    }
}
