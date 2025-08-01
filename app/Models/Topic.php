<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Sluggable;
use App\Traits\HasAuthor;
use App\Traits\HasReads;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property int $forum_id
 * @property int $created_by
 * @property bool $is_pinned
 * @property bool $is_locked
 * @property int $views_count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $author
 * @property-read mixed $author_name
 * @property-read User $creator
 * @property-read Forum $forum
 * @property-read bool $is_hot
 * @property-read bool $is_read_by_user
 * @property-read Post|null $lastPost
 * @property-read mixed $last_reply_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Post> $posts
 * @property-read int|null $posts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Read> $reads
 * @property-read int $reads_count
 *
 * @method static \Database\Factories\TopicFactory factory($count = null, $state = [])
 * @method static Builder<static>|Topic latestActivity()
 * @method static Builder<static>|Topic newModelQuery()
 * @method static Builder<static>|Topic newQuery()
 * @method static Builder<static>|Topic notPinned()
 * @method static Builder<static>|Topic pinned()
 * @method static Builder<static>|Topic query()
 * @method static Builder<static>|Topic unlocked()
 * @method static Builder<static>|Topic whereCreatedAt($value)
 * @method static Builder<static>|Topic whereCreatedBy($value)
 * @method static Builder<static>|Topic whereDescription($value)
 * @method static Builder<static>|Topic whereForumId($value)
 * @method static Builder<static>|Topic whereId($value)
 * @method static Builder<static>|Topic whereIsLocked($value)
 * @method static Builder<static>|Topic whereIsPinned($value)
 * @method static Builder<static>|Topic whereSlug($value)
 * @method static Builder<static>|Topic whereTitle($value)
 * @method static Builder<static>|Topic whereUpdatedAt($value)
 * @method static Builder<static>|Topic whereViewsCount($value)
 *
 * @mixin \Eloquent
 */
class Topic extends Model implements Sluggable
{
    use HasAuthor;
    use HasFactory;
    use HasReads;
    use HasSlug;
    use Searchable;

    protected $fillable = [
        'title',
        'description',
        'forum_id',
        'is_pinned',
        'is_locked',
        'views_count',
        'last_reply_at',
    ];

    protected $appends = [
        'posts_count',
        'last_reply_at',
        'is_hot',
    ];

    protected $touches = [
        'forum',
    ];

    public function generateSlug(): string
    {
        return Str::slug($this->title);
    }

    public function forum(): BelongsTo
    {
        return $this->belongsTo(Forum::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class)->where('type', 'forum');
    }

    public function lastPost(): HasOne
    {
        return $this->hasOne(Post::class)
            ->ofMany([
                'id' => 'max',
            ], function (Builder $query) {
                $query->where('type', 'forum');
            });
    }

    public function lastReplyAt(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->lastPost?->created_at
        )->shouldCache();
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopeNotPinned($query)
    {
        return $query->where('is_pinned', false);
    }

    public function scopeUnlocked($query)
    {
        return $query->where('is_locked', false);
    }

    public function scopeLatestActivity($query)
    {
        return $query->orderByDesc('is_pinned')
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at');
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    public function postsCount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->posts()->count(),
        )->shouldCache();
    }

    public function isHot(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                $weekAgo = now()->subWeek();
                if ($this->created_at < $weekAgo) {
                    return false;
                }

                $dayAgo = now()->subDay();
                $recentPosts = $this->posts()
                    ->where('created_at', '>=', $dayAgo)
                    ->with('likes')
                    ->get();

                $postsInLast24h = $recentPosts->count();
                $postingScore = $postsInLast24h * 2;

                $likesInLast24h = $recentPosts->sum(function ($post) {
                    return $post->likes()->count();
                });
                $engagementScore = $likesInLast24h * 1;

                $totalScore = $postingScore + $engagementScore;

                return $totalScore >= 10;
            }
        )->shouldCache();
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'created_at' => $this->created_at?->toDateTimeString() ?? '',
        ];
    }

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'is_locked' => 'boolean',
            'last_reply_at' => 'datetime',
        ];
    }
}
