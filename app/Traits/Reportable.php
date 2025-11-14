<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Report;
use Eloquent;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @mixin Eloquent
 */
trait Reportable
{
    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function hasReports(): bool
    {
        return $this->pendingReports->isNotEmpty();
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

    protected function initializeReportable(): void
    {
        $this->mergeAppends([
            'is_reported',
        ]);
    }
}
