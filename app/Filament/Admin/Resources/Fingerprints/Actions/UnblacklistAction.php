<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Fingerprints\Actions;

use App\Models\Fingerprint;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Override;

class UnblacklistAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Unblacklist Fingerprint');
        $this->icon(Heroicon::OutlinedCheckCircle);
        $this->color('success');
        $this->visible(fn (Fingerprint $record): bool => $record->is_blacklisted);
        $this->requiresConfirmation();
        $this->modalHeading('Unblacklist Fingerprint');
        $this->modalDescription('Are you sure you want to this fingerprint from the blacklist?');
        $this->modalSubmitActionLabel('Unblacklist Fingerprint');
        $this->successNotificationTitle('The fingerprint has been successfully removed from the blacklist.');
        $this->action(function (UnblacklistAction $action, Fingerprint $record): void {
            $record->unblacklistResource();
            $action->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'unblacklist_fingerprint';
    }
}
