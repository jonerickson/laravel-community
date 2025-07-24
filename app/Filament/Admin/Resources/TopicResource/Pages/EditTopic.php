<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TopicResource\Pages;

use App\Filament\Admin\Resources\TopicResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTopic extends EditRecord
{
    protected static string $resource = TopicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
