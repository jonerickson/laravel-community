<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ApiTokens\Pages;

use App\Filament\Admin\Resources\ApiTokens\ApiTokenResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListApiTokens extends ListRecords
{
    protected static string $resource = ApiTokenResource::class;

    protected ?string $subheading = 'Manage your system API keys that allow programatic access to your app data.';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('documentation')
                ->color('gray')
                ->url(fn (): string => config('app.url').'/api/v1', shouldOpenInNewTab: true),
            CreateAction::make(),
        ];
    }
}
