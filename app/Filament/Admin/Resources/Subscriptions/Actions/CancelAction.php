<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Subscriptions\Actions;

use App\Managers\PaymentManager;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Support\Icons\Heroicon;
use Laravel\Cashier\Subscription;

class CancelAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Cancel');
        $this->color('danger');
        $this->icon(Heroicon::OutlinedXCircle);
        $this->successNotificationTitle('The subscription has been successfully cancelled.');
        $this->requiresConfirmation();
        $this->modalHeading('Cancel Subscription');
        $this->modalDescription('Are you sure you want to cancel this subscription?');
        $this->schema([
            Checkbox::make('cancel_now')
                ->default(false)
                ->inline()
                ->helperText('Cancel the subscription immediately. If left unchecked, the subscription will cancel at the end of the billing cycle.'),
        ]);
        $this->action(function (Subscription $record, array $data) {
            $paymentManager = app(PaymentManager::class);
            $paymentManager->cancelSubscription(
                user: $record->user,
                cancelNow: data_get($data, 'cancel_now'),
            );
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'cancel';
    }
}
