<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ApiTokens\Pages;

use App\Filament\Admin\Resources\ApiTokens\ApiTokenResource;
use App\Models\User;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Resources\Pages\CreateRecord;
use Laravel\Passport\Token;
use Override;

class CreateApiToken extends CreateRecord
{
    use InteractsWithActions;

    protected static string $resource = ApiTokenResource::class;

    #[Override]
    protected function handleRecordCreation(array $data): Token
    {
        $user = User::find($data['tokenable_id']);
        $result = $user->createToken(
            $data['name'],
            $data['abilities'] ?? ['*'],
        );

        return $result->getToken();
    }

    #[Override]
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
