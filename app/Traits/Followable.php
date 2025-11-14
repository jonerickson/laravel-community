<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Follow;
use App\Models\User;
use Eloquent;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * @mixin Eloquent
 */
trait Followable
{
    public function follows(): MorphMany
    {
        return $this->morphMany(Follow::class, 'followable');
    }

    public function followers(): MorphMany
    {
        return $this->follows();
    }

    public function userFollow(?User $user = null): ?Follow
    {
        $user ??= Auth::user();

        if (! $user) {
            return null;
        }

        return $this->follows->firstWhere('created_by', $user->getKey());
    }

    public function isFollowedBy(?User $user = null): bool
    {
        return $this->userFollow($user) !== null;
    }

    public function follow(?User $user = null): Model
    {
        $user ??= Auth::user();

        if (! $user) {
            throw new RuntimeException('User must be authenticated to follow.');
        }

        return $this->follows()->updateOrCreate([
            'created_by' => $user->getKey(),
        ]);
    }

    public function unfollow(?User $user = null): bool
    {
        $user ??= Auth::user();

        if (! $user) {
            return false;
        }

        return $this->follows()
            ->where('created_by', $user->id)
            ->delete() > 0;
    }

    public function isFollowedByUser(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                $user = Auth::user();

                if (! $user) {
                    return false;
                }

                return $this->isFollowedBy($user);
            }
        )->shouldCache();
    }

    protected static function bootFollowable(): void
    {
        static::deleting(function (Model $model): void {
            /** @var static $model */
            $model->follows()->delete();
        });
    }
}
