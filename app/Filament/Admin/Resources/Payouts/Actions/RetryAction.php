<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Payouts\Actions;

use App\Actions\Payouts\RetryPayoutAction;
use App\Models\Payout;
use Exception;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Override;

class RetryAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Retry');
        $this->requiresConfirmation();
        $this->icon(Heroicon::OutlinedArrowPath);
        $this->color('info');
        $this->modalDescription('Are you sure you want to retry the payout? This will attempt to initialize and process the payout once more.');
        $this->visible(fn (Payout $payout): bool => $payout->canRetry());
        $this->action(function (RetryAction $action, Payout $payout): void {
            try {
                RetryPayoutAction::execute($payout);
            } catch (Exception $exception) {
                $action->failureNotificationTitle($exception->getMessage());
                $action->failure();
            }
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'retry_action';
    }
}
