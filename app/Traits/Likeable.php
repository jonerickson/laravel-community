<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Like;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

trait Likeable
{
    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function likesByEmoji(string $emoji): MorphMany
    {
        $unicode = self::emojiToUnicode($emoji);

        return $this->likes()->where('emoji', $unicode);
    }

    public function userLike(?int $userId = null): ?Like
    {
        $userId ??= Auth::id();

        if (! $userId) {
            return null;
        }

        return $this->likes()->where('created_by', $userId)->first();
    }

    public function userLikes(?int $userId = null): Collection
    {
        $userId ??= Auth::id();

        if (! $userId) {
            return collect();
        }

        return $this->likes()->where('created_by', $userId)->get();
    }

    public function userLikeForEmoji(string $emoji, ?int $userId = null): ?Like
    {
        $userId ??= Auth::id();

        if (! $userId) {
            return null;
        }

        $unicode = self::emojiToUnicode($emoji);

        return $this->likes()->where('created_by', $userId)->where('emoji', $unicode)->first();
    }

    public function isLikedBy(?int $userId = null): bool
    {
        return $this->userLike($userId) !== null;
    }

    public function toggleLike(string $emoji = '👍', ?int $userId = null): Like|bool
    {
        $userId ??= Auth::id();

        if (! $userId) {
            return false;
        }

        $existingLike = $this->userLikeForEmoji($emoji, $userId);

        if ($existingLike) {
            $existingLike->delete();

            return true;
        }

        return $this->likes()->create([
            'emoji' => self::emojiToUnicode($emoji),
        ]);
    }

    public function likesSummary(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                $likes = $this->likes->groupBy('emoji');
                $summary = [];

                foreach ($likes as $unicode => $emojiLikes) {
                    $summary[] = [
                        'emoji' => self::unicodeToEmoji($unicode),
                        'count' => $emojiLikes->count(),
                        'users' => $emojiLikes->pluck('author.name')->filter()->take(3)->toArray(),
                    ];
                }

                return collect($summary)->sortByDesc('count')->values()->toArray();
            }
        )->shouldCache();
    }

    public function userReaction(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                $userId = Auth::id();

                if (! $userId) {
                    return null;
                }

                /** @var ?string $unicode */
                $unicode = $this->likes->where('created_by', $userId)->value('emoji');

                return $unicode ? self::unicodeToEmoji($unicode) : null;
            }
        )->shouldCache();
    }

    public function userReactions(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                $userId = Auth::id();

                if (! $userId) {
                    return [];
                }

                $unicodes = $this->likes->where('created_by', $userId)->pluck('emoji')->toArray();

                return array_map(self::unicodeToEmoji(...), $unicodes);
            }
        )->shouldCache();
    }

    protected static function emojiToUnicode(string $emoji): string
    {
        $unicode = '';
        $length = mb_strlen($emoji, 'UTF-8');

        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($emoji, $i, 1, 'UTF-8');
            $codepoint = mb_ord($char, 'UTF-8');
            $unicode .= sprintf('U+%04X', $codepoint);
            if ($i < $length - 1) {
                $unicode .= '_';
            }
        }

        return $unicode;
    }

    protected static function unicodeToEmoji(string $unicode): string
    {
        $parts = explode('_', $unicode);
        $emoji = '';

        foreach ($parts as $part) {
            if (preg_match('/U\+([0-9A-F]+)/', $part, $matches)) {
                $codepoint = hexdec($matches[1]);
                $emoji .= mb_chr($codepoint, 'UTF-8');
            }
        }

        return $emoji;
    }

    protected static function bootLikeable(): void
    {
        static::deleting(function (Model $model): void {
            /** @var static $model */
            $model->likes()->delete();
        });
    }

    protected function initializeLikeable(): void
    {
        $this->mergeAppends([
            'likes_summary',
            'user_reaction',
            'user_reactions',
        ]);
    }
}
