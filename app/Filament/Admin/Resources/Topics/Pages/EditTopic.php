<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Topics\Pages;

use App\Filament\Admin\Resources\Topics\TopicResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTopic extends EditRecord
{
    protected static string $resource = TopicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
