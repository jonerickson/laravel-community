<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Whitelists\Pages;

use App\Filament\Admin\Resources\Whitelists\WhitelistResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWhitelists extends ListRecords
{
    protected static string $resource = WhitelistResource::class;

    protected ?string $subheading = 'The whitelist provides a way to explicitly prevent certain content from being automatically blacklisted or screened.';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
