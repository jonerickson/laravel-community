<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\Actions\BlacklistAction;
use App\Filament\Admin\Resources\Users\Actions\ImpersonateAction;
use App\Filament\Admin\Resources\Users\Actions\SyncGroupsAction;
use App\Filament\Admin\Resources\Users\Actions\UnblacklistAction;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Override;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    #[Override]
    public function getSubheading(): string|Htmlable|null
    {
        return Str::limit($this->record?->email);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('profile')
                ->color('gray')
                ->url(fn (User $record): string => route('users.show', $record), shouldOpenInNewTab: true),
            DeleteAction::make(),
            ActionGroup::make([
                ImpersonateAction::make(),
                BlacklistAction::make(),
                UnblacklistAction::make(),
                SyncGroupsAction::make()
                    ->user(fn (User $record): Model|int|string|null => $this->record),
            ]),
        ];
    }
}
