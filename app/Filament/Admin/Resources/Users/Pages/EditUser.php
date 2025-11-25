<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\Actions\BanAction;
use App\Filament\Admin\Resources\Users\Actions\SyncGroupsAction;
use App\Filament\Admin\Resources\Users\Actions\UnbanAction;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('profile')
                ->color('gray')
                ->url(fn (User $record): string => route('users.show', $record), shouldOpenInNewTab: true),
            DeleteAction::make(),
            ActionGroup::make([
                BanAction::make(),
                UnbanAction::make(),
                SyncGroupsAction::make()
                    ->user(fn (User $record): Model|int|string|null => $this->record),
            ]),
        ];
    }
}
