<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Payouts\Actions;

use App\Actions\Payouts\CancelPayoutAction;
use App\Models\Payout;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;
use Override;

class CancelAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Cancel');
        $this->requiresConfirmation();
        $this->icon(Heroicon::OutlinedXCircle);
        $this->color('danger');
        $this->modalDescription('Are you sure you want to cancel the payout?');
        $this->visible(fn (Payout $payout): bool => $payout->canCancel());
        $this->schema([
            Textarea::make('reason')
                ->maxLength(255)
                ->required(),
        ]);
        $this->action(function (CancelAction $action, Payout $payout, array $data): void {
            try {
                CancelPayoutAction::execute($payout, data_get($data, 'reason'));
            } catch (Exception $exception) {
                $action->failureNotificationTitle($exception->getMessage());
                $action->failure();
            }
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'cancel_action';
    }
}
