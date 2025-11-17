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

        if (method_exists($this, 'getLoggedAttributes')) {
            $options->logOnly($this->getLoggedAttributes());
        } else {
            $options->logFillable();
        }

        if (method_exists($this, 'getActivityDescription')) {
            $options->setDescriptionForEvent(fn (string $eventName) => $this->getActivityDescription($eventName));
        } else {
            $modelName = class_basename($this);
            $options->setDescriptionForEvent(fn (string $eventName): string => sprintf('%s %s', $modelName, $eventName));
        }

        if (method_exists($this, 'getActivityLogName')) {
            $options->useLogName($this->getActivityLogName());
        }

        return $options;
    }
}
