<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PaymentMethods\Actions;

use App\Managers\PaymentManager;
use App\Models\User;
use Closure;
use Filament\Actions\Action;
use Override;

class NewAction extends Action
{
    protected User|Closure|null $user = null;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('New payment method');
        $this->color('primary');
        $this->successNotificationTitle('The payment method has been successfully added.');

        $this->action(function (NewAction $action, array $data): void {
            $paymentManager = app(PaymentManager::class);
            $paymentManager->createPaymentMethod(
                user: $this->getUser(),
                paymentMethodId: ''
            );

            $action->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'new';
    }

    public function getUser(): mixed
    {
        return $this->evaluate($this->user);
    }

    public function user(User|Closure|null $user): static
    {
        $this->user = $user;

        return $this;
    }
}
