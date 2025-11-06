<?php

declare(strict_types=1);

namespace App\Listeners\Reports;

use App\Enums\Role;
use App\Events\ReportCreated;
use App\Models\User;
use App\Notifications\Reports\NewReportCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Notification;

class SendAdminNotification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;

    public function handle(ReportCreated $event): void
    {
        if (App::runningConsoleCommand('app:migrate')) {
            return;
        }

        $admins = User::query()
            ->role(Role::Administrator)
            ->get();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new NewReportCreatedNotification($event->report));
    }
}
