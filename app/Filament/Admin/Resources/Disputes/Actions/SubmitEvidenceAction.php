<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Disputes\Actions;

use App\Actions\Policies\GenerateDisputeEvidenceAction;
use App\Enums\DisputeStatus;
use App\Facades\PaymentProcessor;
use App\Models\Dispute;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Override;

class SubmitEvidenceAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Submit Evidence');
        $this->color('success');
        $this->icon(Heroicon::OutlinedDocumentCheck);
        $this->requiresConfirmation();
        $this->modalHeading('Submit Dispute Evidence');
        $this->modalDescription('This will generate an evidence PDF from the order and submit it to the payment processor. Are you sure?');
        $this->modalSubmitActionLabel('Submit Evidence');
        $this->successNotificationTitle('Dispute evidence submitted successfully.');
        $this->failureNotificationTitle('Failed to submit dispute evidence.');
        $this->visible(fn (Dispute $record): bool => in_array($record->status, [
            DisputeStatus::NeedsResponse,
            DisputeStatus::WarningNeedsResponse,
        ]));
        $this->action(function (Dispute $record, Action $action): void {
            $pdf = GenerateDisputeEvidenceAction::execute($record->order);

            $tempPath = storage_path('app/private/dispute-evidence-'.$record->external_dispute_id.'.pdf');
            $pdf->save($tempPath);

            try {
                $result = PaymentProcessor::submitDisputeEvidence($record, $tempPath);

                if ($result) {
                    $action->success();
                } else {
                    $action->failure();
                }
            } finally {
                if (file_exists($tempPath)) {
                    unlink($tempPath);
                }
            }
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'submitEvidence';
    }
}
