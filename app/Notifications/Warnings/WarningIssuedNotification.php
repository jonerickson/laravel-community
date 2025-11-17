<?php

declare(strict_types=1);

namespace App\Notifications\Warnings;

use App\Mail\Warnings\WarningIssuedMail;
use App\Models\User;
use App\Models\UserWarning;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class WarningIssuedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public UserWarning $userWarning
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(User $notifiable): WarningIssuedMail
    {
        return new WarningIssuedMail($this->userWarning, $notifiable)->to($notifiable->email);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'warning_id' => $this->userWarning->warning_id,
            'warning_name' => $this->userWarning->warning->name,
            'points' => $this->userWarning->warning->points,
            'reason' => $this->userWarning->reason,
            'points_expire_at' => $this->userWarning->points_expire_at,
            'consequence_expires_at' => $this->userWarning->consequence_expires_at,
            'points_at_issue' => $this->userWarning->points_at_issue,
            'warning_consequence_id' => $this->userWarning->warning_consequence_id,
            'consequence_type' => $this->userWarning->warningConsequence?->type,
        ];
    }
}
