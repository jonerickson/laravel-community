<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Subscriptions\Actions;

use App\Managers\PaymentManager;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Laravel\Cashier\Subscription;

class ContinueAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Continue');
        $this->color('success');
        $this->icon(Heroicon::OutlinedCheckCircle);
        $this->successNotificationTitle('The subscription has been successfully continued.');
        $this->requiresConfirmation();
        $this->modalHeading('Continue Subscription');
        $this->modalDescription('Are you sure you want to continue this subscription?');
        $this->action(function (Subscription $record) {
            $paymentManager = app(PaymentManager::class);
            $paymentManager->continueSubscription(
                user: $record->user,
            );
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'continue';
    }
}
