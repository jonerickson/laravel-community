<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Blacklists\Pages;

use App\Filament\Admin\Resources\Blacklists\BlacklistResource;
use App\Filament\Imports\BlacklistImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListBlacklists extends ListRecords
{
    protected static string $resource = BlacklistResource::class;

    protected ?string $subheading = 'The blacklist provides a way to explicitly prevent certain content from being inputted from your users. It applies to every publicly available input field on the frontend.';

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(BlacklistImporter::class),
            CreateAction::make(),
        ];
    }
}
