<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Subscriptions\Actions;

use App\Data\SubscriptionData;
use App\Managers\PaymentManager;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Support\Icons\Heroicon;
use Override;

class CancelAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Cancel');
        $this->color('danger');
        $this->icon(Heroicon::OutlinedXCircle);
        $this->successNotificationTitle('The subscription has been successfully cancelled.');
        $this->failureNotificationTitle('The subscription could not be cancelled. Please try again.');
        $this->requiresConfirmation();
        $this->visible(fn (array $record): bool => SubscriptionData::from($record)->status->canCancel());
        $this->modalHeading('Cancel Subscription');
        $this->modalDescription('Are you sure you want to cancel this subscription?');

        $this->schema([
            Checkbox::make('cancel_now')
                ->label('Cancel Now')
                ->default(false)
                ->inline()
                ->helperText('Cancel the subscription immediately. If left unchecked, the subscription will cancel at the end of the billing cycle.'),
        ]);

        $this->action(function (array $record, array $data, Action $action): void {
            $subscription = SubscriptionData::from($record);

            $paymentManager = app(PaymentManager::class);
            $result = $paymentManager->cancelSubscription(
                user: User::find($subscription->user?->id),
                cancelNow: data_get($data, 'cancel_now'),
            );

            if ($result) {
                $action->success();
            } else {
                $action->failure();
            }
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'cancel';
    }
}
