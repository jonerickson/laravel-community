<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Disputes\Actions;

use App\Facades\PaymentProcessor;
use App\Models\Dispute;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Override;

class CancelSubscriptionAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Cancel Subscription');
        $this->color('warning');
        $this->icon(Heroicon::OutlinedXCircle);
        $this->requiresConfirmation();
        $this->modalHeading('Cancel Subscription');
        $this->modalDescription("Are you sure you want to cancel this user's subscription? This action cannot be undone.");
        $this->modalSubmitActionLabel('Cancel Subscription');
        $this->successNotificationTitle('Subscription has been cancelled.');
        $this->failureNotificationTitle('Failed to cancel subscription.');
        $this->action(function (Dispute $record, Action $action): void {
            $result = PaymentProcessor::cancelSubscription(
                user: $record->user,
                cancelNow: true,
                reason: 'Dispute '.$record->external_dispute_id.' received',
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
        return 'cancelSubscription';
    }
}
