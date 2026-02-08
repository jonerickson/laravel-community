<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Disputes\Actions;

use App\Actions\Users\BlacklistUserAction as BlacklistUser;
use App\Models\Dispute;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;
use Override;

class BlacklistUserAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Blacklist User');
        $this->color('danger');
        $this->icon(Heroicon::OutlinedNoSymbol);
        $this->requiresConfirmation();
        $this->modalHeading('Blacklist User');
        $this->modalDescription('Are you sure you want to blacklist this user? This will prevent them from accessing the platform.');
        $this->modalSubmitActionLabel('Blacklist');
        $this->successNotificationTitle('User has been blacklisted.');
        $this->schema([
            Textarea::make('reason')
                ->label('Reason')
                ->required()
                ->default(fn (Dispute $record): string => 'Dispute '.$record->external_dispute_id.' received')
                ->helperText('Provide a reason for blacklisting this user.'),
        ]);
        $this->visible(fn (Dispute $record): bool => ! $record->user->is_blacklisted);
        $this->action(function (Dispute $record, array $data, Action $action): void {
            BlacklistUser::execute($record->user, $data['reason']);

            $action->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'blacklistUser';
    }
}
