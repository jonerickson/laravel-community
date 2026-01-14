<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Actions;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Override;

class ImpersonateAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->icon(Heroicon::OutlinedEye);
        $this->requiresConfirmation();
        $this->modalIcon(Heroicon::OutlinedEye);
        $this->modalDescription('You will be signed in as this user and all actions performed will be under this user account.');
        $this->modalSubmitActionLabel('Impersonate');
        $this->visible(fn (?User $record): bool => $record->canBeImpersonated() && Auth::user()->canImpersonate());

        $this->action(function (User $record): void {
            Auth::user()->impersonate($record);

            $this->redirect(route('home'));
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'impersonate';
    }
}
