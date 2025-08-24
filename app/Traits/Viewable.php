<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\View;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

trait Viewable
{
    public function views(): MorphMany
    {
        return $this->morphMany(View::class, 'viewable');
    }

    public function userView(?int $userId = null): ?View
    {
        $userId = $userId ?? Auth::id();

        if (! $userId) {
            return null;
        }

        return $this->views()->where('created_by', $userId)->first();
    }

    public function isViewedBy(?int $userId = null): bool
    {
        return $this->userView($userId) !== null;
    }

    public function recordView(?int $userId = null): View|bool
    {
        $userId ??= Auth::id();

        if (! $userId) {
            return false;
        }

        $existingView = $this->userView($userId);
        if ($existingView) {
            $existingView->increment('count');

            return $existingView;
        }

        return $this->views()->updateOrCreate([
            'created_by' => $userId,
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
            get: fn (): int => $this->views()->distinct('created_by')->count('created_by'),
        )->shouldCache();
    }

    public function incrementViews(): void
    {
        $this->recordView();
    }

    protected function initializeViewable(): void
    {
        $this->mergeAppends([
            'views_count',
            'unique_views_count',
        ]);
    }
}
