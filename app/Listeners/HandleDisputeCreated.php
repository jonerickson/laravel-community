<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Users\BlacklistUserAction;
use App\Enums\DisputeAction;
use App\Events\DisputeCreated;
use App\Facades\PaymentProcessor;
use App\Settings\DisputeSettings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class HandleDisputeCreated implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;

    public function __construct(private readonly DisputeSettings $settings) {}

    public function handle(DisputeCreated $event): void
    {
        $actions = collect($this->settings->dispute_actions)
            ->map(fn (string $value): ?DisputeAction => DisputeAction::tryFrom($value))
            ->filter();

        if ($actions->contains(DisputeAction::Nothing)) {
            Log::info('Dispute automated action: Nothing configured, skipping all actions', [
                'dispute_id' => $event->dispute->id,
            ]);

            return;
        }

        $dispute = $event->dispute;
        $user = $dispute->user;

        foreach ($actions as $action) {
            match ($action) {
                DisputeAction::BlacklistUser => $this->blacklistUser($dispute, $user),
                DisputeAction::CancelSubscription => $this->cancelSubscription($dispute, $user),
                DisputeAction::FlagForReview => $this->flagForReview($dispute),
                default => null,
            };
        }
    }

    private function blacklistUser(\App\Models\Dispute $dispute, \App\Models\User $user): void
    {
        if ($user->is_blacklisted) {
            Log::info('Dispute automated action: User already blacklisted, skipping', [
                'dispute_id' => $dispute->id,
                'user_id' => $user->id,
            ]);

            return;
        }

        BlacklistUserAction::execute($user, "Automated: Dispute {$dispute->external_dispute_id} received");

        Log::info('Dispute automated action: User blacklisted', [
            'dispute_id' => $dispute->id,
            'user_id' => $user->id,
        ]);
    }

    private function cancelSubscription(\App\Models\Dispute $dispute, \App\Models\User $user): void
    {
        $result = PaymentProcessor::cancelSubscription($user, true, "Automated: Dispute {$dispute->external_dispute_id} received");

        Log::info('Dispute automated action: Cancel subscription', [
            'dispute_id' => $dispute->id,
            'user_id' => $user->id,
            'result' => $result,
        ]);
    }

    private function flagForReview(\App\Models\Dispute $dispute): void
    {
        Log::info('Dispute automated action: Flagged for review', [
            'dispute_id' => $dispute->id,
            'status' => $dispute->status->value,
        ]);
    }
}
