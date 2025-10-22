<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Report;

class ReportCreated
{
    public function __construct(public Report $report)
    {
        //
    }
}
