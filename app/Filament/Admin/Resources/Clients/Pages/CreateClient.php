<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Clients\Pages;

use App\Filament\Admin\Resources\Clients\ClientResource;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Laravel\Passport\ClientRepository;
use Override;

class CreateClient extends CreateRecord
{
    use InteractsWithActions;

    protected static string $resource = ClientResource::class;

    protected ClientRepository $clientRepository;

    public function __construct()
    {
        $this->clientRepository = app(ClientRepository::class);
    }

    #[Override]
    protected function handleRecordCreation(array $data): Model
    {
        return match (Arr::pull($data, 'grant_types')) {
            'authorization_code' => $this->clientRepository->createAuthorizationCodeGrantClient(
                name: $data['name'],
                redirectUris: $data['redirect_uris'],
            ),
            'password' => $this->clientRepository->createPasswordGrantClient(
                name: $data['name'],
            ),
            'client_credentials' => $this->clientRepository->createClientCredentialsGrantClient(
                name: $data['name'],
            ),
            'implicit' => $this->clientRepository->createImplicitGrantClient(
                name: $data['name'],
                redirectUris: $data['redirect_uris'],
            )
        };
    }

    #[Override]
    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Your OAuth client was successfully created. Copy the secret below as it will not be shown again.')
            ->body($this->record->plainSecret ?? 'No secret was specified.');
    }
}
