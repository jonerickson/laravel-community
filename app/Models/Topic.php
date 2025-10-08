<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Sluggable;
use App\Events\TopicCreated;
use App\Traits\Followable;
use App\Traits\HasAuthor;
use App\Traits\HasLogging;
use App\Traits\HasSlug;
use App\Traits\Lockable;
use App\Traits\Pinnable;
use App\Traits\Readable;
use App\Traits\Trendable;
use App\Traits\Viewable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property int $forum_id
 * @property bool $is_pinned
 * @property bool $is_locked
 * @property int $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read User $author
 * @property-read mixed $author_name
 * @property-read User $creator
 * @property-read Collection<int, Follow> $followers
 * @property-read int $followers_count
 * @property-read Collection<int, Follow> $follows
 * @property-read int|null $follows_count
 * @property-read Forum $forum
 * @property-read bool $has_reported_content
 * @property-read bool $has_unapproved_content
 * @property-read bool $has_unpublished_content
 * @property-read bool $is_followed_by_user
 * @property-read bool $is_hot
 * @property-read bool $is_read_by_user
 * @property-read Post|null $lastPost
 * @property-read mixed $last_reply_at
 * @property-read Collection<int, Post> $posts
 * @property-read int|null $posts_count
 * @property-read Collection<int, Read> $reads
 * @property-read int $reads_count
 * @property-read float $trending_score
 * @property-read int $unique_views_count
 * @property-read Collection<int, View> $views
 * @property-read string|int $views_count
 *
 * @method static \Database\Factories\TopicFactory factory($count = null, $state = [])
 * @method static Builder<static>|Topic hotTopics(?int $limit = null)
 * @method static Builder<static>|Topic latestActivity()
 * @method static Builder<static>|Topic locked()
 * @method static Builder<static>|Topic newModelQuery()
 * @method static Builder<static>|Topic newQuery()
 * @method static Builder<static>|Topic notPinned()
 * @method static Builder<static>|Topic pinned()
 * @method static Builder<static>|Topic query()
 * @method static Builder<static>|Topic read(?\App\Models\User $user = null)
 * @method static Builder<static>|Topic risingTopics(?int $limit = null)
 * @method static Builder<static>|Topic trending(?int $limit = null, ?\Illuminate\Support\Carbon $referenceTime = null)
 * @method static Builder<static>|Topic trendingInTimeframe(string $timeframe = 'week', ?int $limit = null)
 * @method static Builder<static>|Topic unlocked()
 * @method static Builder<static>|Topic unread(?\App\Models\User $user = null)
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
 *
 * @mixin \Eloquent
 */
class Topic extends Model implements Sluggable
{
    use Followable;
    use HasAuthor;
    use HasFactory;
    use HasLogging;
    use HasSlug;
    use Lockable;
    use Pinnable;
    use Readable;
    use Searchable;
    use Trendable;
    use Viewable;

    protected $fillable = [
        'title',
        'description',
        'forum_id',
        'last_reply_at',
    ];

    protected $appends = [
        'posts_count',
        'last_reply_at',
        'is_hot',
        'has_reported_content',
        'has_unpublished_content',
        'has_unapproved_content',
    ];

    protected $touches = [
        'forum',
    ];

    protected $dispatchesEvents = [
        'created' => TopicCreated::class,
    ];

    public function generateSlug(): ?string
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
            ], function (Builder $query): void {
                $query->where('type', 'forum');
            });
    }

    public function lastReplyAt(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->lastPost?->created_at
        )->shouldCache();
    }

    public function scopeLatestActivity(Builder $query): void
    {
        $query->orderByDesc('is_pinned')
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at');
    }

    public function postsCount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->posts()->count(),
        )->shouldCache();
    }

    public function hasReportedContent(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->posts()
                ->whereHas('pendingReports')
                ->exists()
        )->shouldCache();
    }

    public function hasUnpublishedContent(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->posts()
                ->unpublished()
                ->exists()
        )->shouldCache();
    }

    public function hasUnapprovedContent(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->posts()
                ->unapproved()
                ->exists()
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

                $likesInLast24h = $recentPosts->sum(fn ($post) => $post->likes()->count());
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

    public function getLoggedAttributes(): array
    {
        return [
            'title',
            'description',
            'is_pinned',
            'is_locked',
            'forum_id',
        ];
    }

    public function getActivityDescription(string $eventName): string
    {
        $title = $this->title ? " \"{$this->title}\"" : '';

        return "Forum topic{$title} $eventName";
    }

    public function getActivityLogName(): string
    {
        return 'forum';
    }

    protected function casts(): array
    {
        return [
            'last_reply_at' => 'datetime',
        ];
    }
}
