<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TopicResource\Pages;

use App\Filament\Admin\Resources\TopicResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTopics extends ListRecords
{
    protected static string $resource = TopicResource::class;

    protected ?string $subheading = 'Manage your forum topics.';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
