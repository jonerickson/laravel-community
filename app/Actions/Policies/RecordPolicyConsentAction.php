<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Actions\Action;
use App\Enums\PolicyConsentContext;
use App\Models\Policy;
use App\Models\PolicyConsent;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RecordPolicyConsentAction extends Action
{
    /**
     * @param  array<int>|Collection<int, Policy>  $policies
     */
    public function __construct(
        protected User $user,
        protected array|Collection $policies,
        protected PolicyConsentContext $context,
        protected ?string $ipAddress = null,
        protected ?string $userAgent = null,
        protected ?string $fingerprintId = null,
    ) {}

    public function __invoke(): mixed
    {
        $policyIds = $this->policies instanceof Collection
            ? $this->policies->pluck('id')->all()
            : $this->policies;

        foreach ($policyIds as $policyId) {
            PolicyConsent::updateOrCreate(
                [
                    'user_id' => $this->user->id,
                    'policy_id' => $policyId,
                    'context' => $this->context,
                ],
                [
                    'ip_address' => $this->ipAddress,
                    'user_agent' => $this->userAgent,
                    'fingerprint_id' => $this->fingerprintId,
                    'consented_at' => Carbon::now(),
                ],
            );
        }

        return null;
    }
}
