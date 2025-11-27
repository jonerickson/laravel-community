<?php

declare(strict_types=1);

namespace App\Traits;

use App\Enums\FilterType;
use App\Models\Blacklist;
use App\Models\Fingerprint;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @mixin \Eloquent
 */
trait Blacklistable
{
    public function blacklist(): MorphOne
    {
        return $this->morphOne(Blacklist::class, 'resource');
    }

    public function isBlacklisted(): Attribute
    {
        return Attribute::get(fn (): bool => $this->blacklist()->exists())
            ->shouldCache();
    }

    public function blacklistResource(string $reason): Blacklist|false
    {
        return $this->blacklist()->create([
            'description' => $reason,
            'filter' => match ($this::class) {
                Fingerprint::class => FilterType::Fingerprint,
                User::class => FilterType::User,
                default => FilterType::String,
            },
        ]);
    }

    public function unblacklistResource(): mixed
    {
        return $this->blacklist()->delete();
    }
}
