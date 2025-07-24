<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Sluggable;
use App\Traits\HasAuthor;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

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
 * @property-read int|null $replies_count
 * @property int|null $last_post_id
 * @property \Illuminate\Support\Carbon|null $last_reply_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $author
 * @property-read mixed $author_name
 * @property-read User $creator
 * @property-read Forum $forum
 * @property-read Post|null $lastPost
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Post> $posts
 * @property-read int|null $posts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Post> $replies
 *
 * @method static \Database\Factories\TopicFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topic latestActivity()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topic newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topic newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topic notPinned()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topic pinned()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topic query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topic unlocked()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topic whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topic whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topic whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topic whereForumId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topic whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topic whereIsLocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topic whereIsPinned($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topic whereLastPostId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topic whereLastReplyAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topic whereRepliesCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topic whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topic whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topic whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topic whereViewsCount($value)
 *
 * @mixin \Eloquent
 */
class Topic extends Model implements Sluggable
{
    use HasAuthor;
    use HasFactory;
    use HasSlug;

    protected $fillable = [
        'title',
        'description',
        'forum_id',
        'created_by',
        'is_pinned',
        'is_locked',
        'views_count',
        'replies_count',
        'last_post_id',
        'last_reply_at',
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
        return $this->hasMany(Post::class)->where('post_type', 'forum');
    }

    public function replies(): HasMany
    {
        return $this->posts()->oldest();
    }

    public function lastPost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'last_post_id');
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
            ->orderByDesc('last_reply_at')
            ->orderByDesc('created_at');
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    public function updateLastReply(Post $post): void
    {
        $this->update([
            'last_post_id' => $post->id,
            'last_reply_at' => $post->created_at,
            'replies_count' => $this->posts()->count(),
        ]);
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
