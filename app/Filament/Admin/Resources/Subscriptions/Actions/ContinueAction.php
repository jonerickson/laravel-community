<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Subscriptions\Actions;

use App\Data\SubscriptionData;
use App\Managers\PaymentManager;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

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
        $this->visible(fn (array $record) => SubscriptionData::from($record)->status->canContinue() && filled(data_get($record, 'endsAt')));
        $this->modalHeading('Continue Subscription');
        $this->modalDescription('Are you sure you want to continue this subscription?');

        $this->action(function (array $record, Action $action) {
            $subscription = SubscriptionData::from($record);

            $paymentManager = app(PaymentManager::class);
            $paymentManager->continueSubscription(
                user: User::find($subscription->user?->id),
            );

            $action->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'continue';
    }
}
