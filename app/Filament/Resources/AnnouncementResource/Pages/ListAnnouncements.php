<?php

declare(strict_types=1);

namespace App\Filament\Resources\AnnouncementResource\Pages;

use App\Filament\Resources\AnnouncementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAnnouncements extends ListRecords
{
    protected static string $resource = AnnouncementResource::class;

    protected ?string $subheading = 'Display global announcements and information to all users via the dashboard.';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
