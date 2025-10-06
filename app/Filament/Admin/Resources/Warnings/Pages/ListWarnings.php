<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Warnings\Pages;

use App\Filament\Admin\Resources\WarningConsequences\Pages\ListWarningConsequences;
use App\Filament\Admin\Resources\Warnings\WarningResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWarnings extends ListRecords
{
    protected static string $resource = WarningResource::class;

    protected ?string $subheading = 'Warnings provide a way to moderate the community by applying restrictions for prohibited behavior.';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('consequences')
                ->color('gray')
                ->url(fn (): string => ListWarningConsequences::getUrl()),
            CreateAction::make(),
        ];
    }
}
