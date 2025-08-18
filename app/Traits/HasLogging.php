<?php

declare(strict_types=1);

namespace App\Traits;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

trait HasLogging
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        $options = LogOptions::defaults()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();

        // Allow models to specify which attributes to log
        if (method_exists($this, 'getLoggedAttributes')) {
            $options->logOnly($this->getLoggedAttributes());
        } else {
            $options->logFillable();
        }

        // Allow models to customize the description
        if (method_exists($this, 'getActivityDescription')) {
            $options->setDescriptionForEvent(fn (string $eventName) => $this->getActivityDescription($eventName));
        } else {
            $modelName = class_basename($this);
            $options->setDescriptionForEvent(fn (string $eventName) => "{$modelName} {$eventName}");
        }

        // Allow models to specify custom log name
        if (method_exists($this, 'getActivityLogName')) {
            $options->useLogName($this->getActivityLogName());
        }

        return $options;
    }
}
