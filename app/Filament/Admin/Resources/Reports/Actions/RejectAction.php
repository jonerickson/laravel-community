<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Reports\Actions;

use App\Models\Report;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Override;

class RejectAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->icon('heroicon-o-x-mark');
        $this->color('danger');
        $this->requiresConfirmation();
        $this->visible(fn (Report $record): bool => $record->isPending());
        $this->successNotificationTitle('The report has been successfully rejected.');
        $this->action(function (Report $record): void {
            $record->reject(Auth::user());
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'reject';
    }
}
