<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Actions;

use App\Models\User;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Hash;
use Override;

class ChangePasswordAction extends Action
{
    protected Closure|User|null $user = null;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->color('gray');
        $this->modalHeading('Change Password');
        $this->modalDescription("Update this user's password.");
        $this->modalSubmitActionLabel('Save');
        $this->successNotificationTitle("The user's password has been successfully changed.");
        $this->failureNotificationTitle("The user's password could not been changed. Please try again later.");

        $this->schema([
            TextInput::make('password')
                ->label('New Password')
                ->password()
                ->revealable()
                ->confirmed()
                ->required(),
            TextInput::make('password_confirmation')
                ->label('Confirm Password')
                ->password()
                ->revealable()
                ->required(),
        ]);

        $this->action(function (ChangePasswordAction $action, array $data): void {
            if (! ($user = $action->getUser()) instanceof User) {
                $action->failure();

                return;
            }

            $user->update([
                'password' => Hash::make($data['password']),
            ]);

            $action->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'change_password';
    }

    public function user(User|Closure|null $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->evaluate($this->user);
    }
}
