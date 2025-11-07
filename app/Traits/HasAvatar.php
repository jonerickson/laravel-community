<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin User
 */
trait HasAvatar
{
    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url;
    }

    public function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes): ?string => isset($attributes['avatar']) ? Storage::url($attributes['avatar']) : null,
        )->shouldCache();
    }

    protected function initializeHasAvatar(): void
    {
        $this->mergeFillable(['avatar']);
        $this->mergeAppends([
            'avatar_url',
        ]);
    }
}
