<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Request;

trait Viewable
{
    public function views(): MorphMany
    {
        return $this->morphMany(View::class, 'viewable');
    }

    public function fingerprintView(?string $fingerprintId = null): ?View
    {
        $fingerprintId ??= request()->fingerprintId();

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
        $fingerprintId ??= Request::fingerprintId();

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

    public function incrementViews(): void
    {
        $this->recordView();
    }

    public function getRecentViewers(int $hours = 24, int $limit = 10): array
    {
        $recentViewers = $this
            ->views()
            ->where('updated_at', '>=', now()->subHours($hours))
            ->whereHas('fingerprint.user')
            ->orderBy('updated_at', 'desc')
            ->distinct()
            ->limit($limit)
            ->get()
            ->values();

        return $recentViewers->reject(fn (View $view): bool => is_null($view->fingerprint?->user))->map(fn (View $view): array => [
            'user' => [
                'id' => $view->fingerprint->user->id,
                'referenceId' => $view->fingerprint->user->reference_id,
                'name' => $view->fingerprint->user->name,
                'avatarUrl' => $view->fingerprint->user->avatar_url,
            ],
            'viewed_at' => $view->updated_at->toISOString(),
        ])->toArray();
    }

    protected static function bootViewable(): void
    {
        static::deleting(function (Model $model): void {
            /** @var static $model */
            $model->views()->delete();
        });
    }
}
