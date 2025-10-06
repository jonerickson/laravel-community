<?php

declare(strict_types=1);

namespace App\Actions\Warnings;

use App\Actions\Action;
use App\Models\User;
use App\Models\UserWarning;
use App\Models\Warning;
use App\Models\WarningConsequence;
use App\Notifications\Warnings\WarningIssuedNotification;

class IssueWarningAction extends Action
{
    public function __construct(
        protected User $user,
        protected Warning $warning,
        protected ?string $reason = null
    ) {
        //
    }

    public function __invoke(): UserWarning
    {
        $newTotalPoints = $this->user->warning_points + $this->warning->points;

        $consequence = WarningConsequence::query()
            ->active()
            ->where('threshold', '<=', $newTotalPoints)
            ->orderByDesc('threshold')
            ->first();

        $pointsExpireAt = now()->addDays($this->warning->days_applied);
        $consequenceExpiresAt = $consequence
            ? now()->addDays($consequence->duration_days)
            : null;

        $userWarning = UserWarning::create([
            'user_id' => $this->user->id,
            'warning_id' => $this->warning->id,
            'warning_consequence_id' => $consequence?->id,
            'reason' => $this->reason,
            'points_at_issue' => $newTotalPoints,
            'points_expire_at' => $pointsExpireAt,
            'consequence_expires_at' => $consequenceExpiresAt,
        ]);

        $this->user->notify(new WarningIssuedNotification($userWarning));

        return $userWarning;
    }
}
