<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Reports\Actions;

use App\Models\Report;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Override;

class ApproveAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->icon('heroicon-o-check');
        $this->color('success');
        $this->requiresConfirmation();
        $this->visible(fn (Report $record): bool => $record->isPending());
        $this->action(function (Report $record): void {
            $record->approve(Auth::user());
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'approve';
    }
}
