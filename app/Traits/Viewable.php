<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\View;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Viewable
{
    public function views(): MorphMany
    {
        return $this->morphMany(View::class, 'viewable');
    }

    public function fingerprintView(?string $fingerprintId = null): ?View
    {
        $fingerprintId ??= $this->getCurrentFingerprintId();

        if (! $fingerprintId) {
            return null;
        }

        return $this->views()->where('fingerprint_id', $fingerprintId)->first();
    }

    public function isViewedByFingerprint(?string $fingerprintId = null): bool
    {
        return $this->fingerprintView($fingerprintId) !== null;
    }

    public function recordView(?string $fingerprintId = null): Model|bool
    {
        $fingerprintId ??= $this->getCurrentFingerprintId();

        if (! $fingerprintId) {
            return false;
        }

        $existingView = $this->fingerprintView($fingerprintId);
        if ($existingView) {
            $existingView->increment('count');

            return $existingView;
        }

        return $this->views()->updateOrCreate([
            'fingerprint_id' => $fingerprintId,
        ], [
            'count' => 1,
        ]);
    }

    public function viewsCount(): Attribute
    {
        return Attribute::make(
            get: fn (): int|string => $this->views()->sum('count'),
        )->shouldCache();
    }

    public function uniqueViewsCount(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->views()->distinct(['fingerprint_id'])->count('fingerprint_id'),
        )->shouldCache();
    }

    public function incrementViews(): void
    {
        $this->recordView();
    }

    protected function getCurrentFingerprintId(): ?string
    {
        return request()->header('X-Fingerprint-ID')
            ?? request()->cookie('fingerprint_id');
    }

    protected function initializeViewable(): void
    {
        $this->mergeAppends([
            'views_count',
            'unique_views_count',
        ]);
    }
}
