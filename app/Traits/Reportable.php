<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Report;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Reportable
{
    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function hasReports(): bool
    {
        return $this->pendingReports()->exists();
    }

    public function isReported(): Attribute
    {
        return Attribute::get(fn (): bool => $this->hasReports())
            ->shouldCache();
    }

    public function pendingReports(): MorphMany
    {
        return $this->reports()->where('status', 'pending');
    }

    public function approvedReports(): MorphMany
    {
        return $this->reports()->where('status', 'approved');
    }

    public function rejectedReports(): MorphMany
    {
        return $this->reports()->where('status', 'rejected');
    }

    public function reportCount(): Attribute
    {
        return Attribute::get(fn (): int => $this->pendingReports()->count())
            ->shouldCache();
    }

    protected function initializeReportable(): void
    {
        $this->setAppends(array_merge($this->getAppends(), [
            'is_reported',
            'report_count',
        ]));
    }
}
