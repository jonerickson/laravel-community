<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Fingerprints\Actions;

use App\Models\Fingerprint;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;
use Override;

class BlacklistAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Blacklist Fingerprint');
        $this->icon(Heroicon::OutlinedXCircle);
        $this->color('danger');
        $this->visible(fn (Fingerprint $record): bool => ! $record->is_blacklisted);
        $this->requiresConfirmation();
        $this->modalHeading('Blacklist Fingerprint');
        $this->modalDescription('Are you sure you want to blacklist this fingerprint? They will be immediately logged out and unable to access the site.');
        $this->modalSubmitActionLabel('Blacklist Fingerprint');
        $this->successNotificationTitle('The fingerprint has been successfully blacklisted.');
        $this->schema([
            Textarea::make('reason')
                ->label('Reason')
                ->required()
                ->maxLength(1000),
        ]);
        $this->action(function (BlacklistAction $action, Fingerprint $record, array $data): void {
            $record->blacklistResource($data['reason']);
            $action->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'blacklist_fingerprint';
    }
}
