<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\PolicyConsentContext;
use App\Models\Policy;
use App\Models\PolicyConsent;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

class BackfillPolicyConsentsCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'app:backfill-policy-consents
                            {--force : Force the operation to run when in production}';

    protected $description = 'Backfill policy consents for all existing users using their registration date.';

    public function handle(): int
    {
        if (! $this->confirmToProceed()) {
            return self::SUCCESS;
        }

        $policyIds = Policy::query()
            ->where('is_active', true)
            ->pluck('id');

        if ($policyIds->isEmpty()) {
            $this->components->warn('No active policies found.');

            return self::SUCCESS;
        }

        $this->components->info(sprintf('Backfilling consents for %d active policies...', $policyIds->count()));

        $count = 0;

        User::query()
            ->with(['fingerprints' => fn ($query) => $query->latest('last_seen_at')->limit(1)])
            ->chunkById(500, function ($users) use ($policyIds, &$count): void {
                $records = [];

                foreach ($users as $user) {
                    $fingerprint = $user->fingerprints->first();

                    foreach ($policyIds as $policyId) {
                        $records[] = [
                            'user_id' => $user->id,
                            'policy_id' => $policyId,
                            'context' => PolicyConsentContext::Onboarding->value,
                            'ip_address' => $fingerprint?->ip_address,
                            'user_agent' => $fingerprint?->user_agent,
                            'fingerprint_id' => $fingerprint?->fingerprint_id,
                            'consented_at' => $user->created_at,
                            'created_at' => $user->created_at,
                            'updated_at' => $user->created_at,
                        ];
                    }
                }

                PolicyConsent::query()->upsert(
                    $records,
                    ['user_id', 'policy_id', 'context'],
                    ['consented_at', 'ip_address', 'user_agent', 'fingerprint_id', 'updated_at'],
                );

                $count += count($users);
                $this->components->twoColumnDetail('Processed users', (string) $count);
            });

        $this->components->success(sprintf('Backfilled policy consents for %d users.', $count));

        return self::SUCCESS;
    }
}
