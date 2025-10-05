<?php

declare(strict_types=1);

namespace App\Notifications\Warnings;

use App\Mail\WarningIssuedMail;
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

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): WarningIssuedMail
    {
        return new WarningIssuedMail($this->userWarning, $notifiable);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'warning_id' => $this->userWarning->warning_id,
            'warning_name' => $this->userWarning->warning->name,
            'points' => $this->userWarning->warning->points,
            'reason' => $this->userWarning->reason,
            'expires_at' => $this->userWarning->expires_at,
            'points_at_issue' => $this->userWarning->points_at_issue,
        ];
    }
}
