<?php

declare(strict_types=1);

namespace App\Notifications\Reports;

use App\Mail\Reports\ReportCreatedMail;
use App\Models\Report;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewReportCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Report $report)
    {
        //
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(User $notifiable): ReportCreatedMail
    {
        return new ReportCreatedMail($this->report)->to($notifiable->email);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'report_id' => $this->report->id,
            'reportable_type' => $this->report->reportable_type,
            'reportable_id' => $this->report->reportable_id,
            'reason' => $this->report->reason->value,
            'reporter_id' => $this->report->author?->id,
            'reporter_name' => $this->report->author?->name,
            'created_at' => $this->report->created_at,
        ];
    }
}
