<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ApiTokens\Pages;

use App\Filament\Admin\Resources\ApiTokens\ApiTokenResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Laravel\Sanctum\PersonalAccessToken;

class CreateApiToken extends CreateRecord
{
    protected static string $resource = ApiTokenResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $abilities = json_decode($data['abilities'], true) ?? ['*'];
        $data['abilities'] = $abilities;

        return $data;
    }

    protected function handleRecordCreation(array $data): PersonalAccessToken
    {
        $user = User::find($data['tokenable_id']);
        $token = $user->createToken(
            $data['name'],
            $data['abilities'],
            $data['expires_at'] ? now()->parse($data['expires_at']) : null
        );

        return $token->accessToken;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
