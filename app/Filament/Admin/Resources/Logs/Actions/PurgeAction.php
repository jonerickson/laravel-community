<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Logs\Actions;

use App\Models\Log;
use Filament\Actions\Action;
use Override;

class PurgeAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Purge');
        $this->color('danger');
        $this->requiresConfirmation();
        $this->modalHeading('Purge Logs');
        $this->modalDescription('Are you sure you want to purge the log? This will delete all logs.');
        $this->modalSubmitActionLabel('Purge Logs');
        $this->successNotificationTitle('The logs have been successfully purged.');
        $this->action(function (PurgeAction $action): void {
            Log::truncate();
            $action->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'purge';
    }
}
