<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ApiTokenResource\Pages;

use App\Filament\Admin\Resources\ApiTokenResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditApiToken extends EditRecord
{
    protected static string $resource = ApiTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Revoke Token')
                ->modalHeading('Revoke API Token')
                ->modalDescription('Are you sure you want to revoke this API token? This action cannot be undone.')
                ->modalSubmitActionLabel('Revoke Token'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['abilities'] = json_encode($data['abilities']);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $abilities = json_decode($data['abilities'], true) ?? ['*'];
        $data['abilities'] = $abilities;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
