<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Fields\Pages;

use App\Filament\Admin\Resources\Fields\FieldResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFields extends ListRecords
{
    protected static string $resource = FieldResource::class;

    protected ?string $subheading = 'Fields provide a way to collect custom user data within your platform.';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
