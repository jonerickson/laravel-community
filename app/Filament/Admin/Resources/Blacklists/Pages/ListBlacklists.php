<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Blacklists\Pages;

use App\Filament\Admin\Resources\Blacklists\BlacklistResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBlacklists extends ListRecords
{
    protected static string $resource = BlacklistResource::class;

    protected ?string $subheading = 'The blacklist provides a way to explicitly prevent certain content from being inputted from your users or to prevent access to the platform from certain accounts.';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
