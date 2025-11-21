<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ApiTokens\Pages;

use App\Filament\Admin\Resources\ApiTokens\ApiTokenResource;
use App\Filament\Admin\Resources\ApiTokens\Widgets\ApiLogActivity;
use App\Filament\Admin\Resources\Logs\LogResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Override;

class ListApiTokens extends ListRecords
{
    public ?string $token = null;

    protected static string $resource = ApiTokenResource::class;

    protected ?string $subheading = 'Manage your system API keys that allow programatic access to your app data.';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('documentation')
                ->color('gray')
                ->url(fn (): string => config('app.url').'/api/v1', shouldOpenInNewTab: true),
            Action::make('logs')
                ->url(LogResource::getIndexUrl([
                    'filters[type][type]' => User::class,
                ]))
                ->color('gray'),
            CreateAction::make()
                ->successNotification(fn (): Notification => Notification::make()
                    ->duration('persistent')
                    ->success()
                    ->title('Your API key was successfully created. Copy the token below as it will not be shown again.')
                    ->body(Str::limit($this->token) ?? 'No token was specified.')
                    ->actions([
                        Action::make('copy')
                            ->label('Copy API Key')
                            ->color('gray')
                            ->dispatch('copy-to-clipboard', ['text' => $this->token])
                            ->close(),
                    ])
                )
                ->using(function (array $data): Model {
                    $user = User::find($data['tokenable_id']);
                    $result = $user->createToken(
                        $data['name'],
                        $data['abilities'] ?? ['*'],
                    );

                    $this->token = $result->accessToken;

                    return $result->getToken();
                }),
        ];
    }

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            ApiLogActivity::class,
        ];
    }
}
