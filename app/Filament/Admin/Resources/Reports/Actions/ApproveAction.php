<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Reports\Actions;

use App\Actions\Warnings\IssueWarningAction;
use App\Models\Report;
use App\Models\User;
use App\Models\Warning;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Width;
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
        $this->successNotificationTitle('The report has been successfully approved.');
        $this->modalWidth(Width::TwoExtraLarge);
        $this->schema([
            Checkbox::make('issue_warning')
                ->label('Issue Warning')
                ->helperText('Issue a warning to the user who created the content?')
                ->live(),
            Select::make('warning_id')
                ->visible(fn (Get $get): mixed => $get('issue_warning'))
                ->label('Warning Type')
                ->options(Warning::active()->pluck('name', 'id'))
                ->required()
                ->searchable(),
            Textarea::make('reason')
                ->visible(fn (Get $get): mixed => $get('issue_warning'))
                ->label('Specific Reason')
                ->rows(3)
                ->maxLength(1000)
                ->placeholder('Optional: provide specific details about this warning instance'),
        ]);
        $this->action(function (ApproveAction $action, Report $record, array $data): void {
            if ($data['issue_warning'] ?? false) {
                $warning = Warning::findOrFail($data['warning_id']);

                if (! ($contentAuthor = $record->getContentAuthor()) instanceof User) {
                    $action->failureNotificationTitle('Unable to find the content author. Please try again.');
                    $action->failure();

                    return;
                }

                IssueWarningAction::execute(
                    $contentAuthor,
                    $warning,
                    $data['reason'] ?? null
                );
            }

            $record->approve();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'approve';
    }
}
