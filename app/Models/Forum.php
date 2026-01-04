<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Sluggable;
use App\Events\ForumCreated;
use App\Events\ForumDeleted;
use App\Events\ForumUpdated;
use App\Traits\Activateable;
use App\Traits\Followable;
use App\Traits\HasForumPermissions;
use App\Traits\HasGroups;
use App\Traits\HasSlug;
use App\Traits\Orderable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Override;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int|null $category_id
 * @property int|null $parent_id
 * @property string|null $rules
 * @property string|null $icon
 * @property string $color
 * @property int $order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ForumCategory|null $category
 * @property-read Collection<int, Forum> $children
 * @property-read int|null $children_count
 * @property-read \App\Data\GroupStyleData|null $display_style
 * @property-read Collection<int, Follow> $followers
 * @property-read int|null $followers_count
 * @property-read Collection<int, Follow> $follows
 * @property-read int|null $follows_count
 * @property-read ForumGroup|null $pivot
 * @property-read Collection<int, Group> $groups
 * @property-read int|null $groups_count
 * @property-read bool $is_followed_by_user
 * @property-read Topic|null $latestTopic
 * @property-read Collection<int, Topic> $latestTopics
 * @property-read int|null $latest_topics_count
 * @property-read Forum|null $parent
 * @property-read Collection<int, Post> $posts
 * @property-read int|null $posts_count
 * @property-read Collection<int, Topic> $topics
 * @property-read int|null $topics_count
 *
 * @method static Builder<static>|Forum active()
 * @method static \Database\Factories\ForumFactory factory($count = null, $state = [])
 * @method static Builder<static>|Forum inactive()
 * @method static Builder<static>|Forum newModelQuery()
 * @method static Builder<static>|Forum newQuery()
 * @method static Builder<static>|Forum ordered()
 * @method static Builder<static>|Forum query()
 * @method static Builder<static>|Forum readableByUser(?\App\Models\User $user = null)
 * @method static Builder<static>|Forum recursiveChildren(int $maxDepth = 3)
 * @method static Builder<static>|Forum whereCategoryId($value)
 * @method static Builder<static>|Forum whereColor($value)
 * @method static Builder<static>|Forum whereCreatedAt($value)
 * @method static Builder<static>|Forum whereDescription($value)
 * @method static Builder<static>|Forum whereIcon($value)
 * @method static Builder<static>|Forum whereId($value)
 * @method static Builder<static>|Forum whereIsActive($value)
 * @method static Builder<static>|Forum whereName($value)
 * @method static Builder<static>|Forum whereOrder($value)
 * @method static Builder<static>|Forum whereParentId($value)
 * @method static Builder<static>|Forum whereRules($value)
 * @method static Builder<static>|Forum whereSlug($value)
 * @method static Builder<static>|Forum whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Forum extends Model implements Sluggable
{
    use Activateable;
    use Followable;
    use HasFactory;
    use HasForumPermissions;
    use HasGroups;
    use HasSlug;
    use Orderable;

    protected $fillable = [
        'name',
        'description',
        'category_id',
        'parent_id',
        'rules',
        'icon',
        'color',
    ];

    protected $dispatchesEvents = [
        'created' => ForumCreated::class,
        'updated' => ForumUpdated::class,
        'deleting' => ForumDeleted::class,
    ];

    public function generateSlug(): ?string
    {
        return Str::slug($this->name);
    }

    public function scopeRecursiveChildren(Builder $query, int $maxDepth = 3): void
    {
        $query->with(['children' => function (HasMany|Forum $query): void {
            $query->ordered()->withCount(['topics', 'posts'])->with(['children' => function (HasMany|Forum $query): void {
                $query->ordered()->withCount(['topics', 'posts'])->with(['children' => function (HasMany|Forum $query): void {
                    $query->ordered()->withCount(['topics', 'posts']);
                }]);
            }]);
        }]);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ForumCategory::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Forum::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Forum::class, 'parent_id');
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class);
    }

    public function latestTopics(): HasMany
    {
        return $this->topics()->latest();
    }

    public function latestTopic(): HasOne
    {
        return $this->hasOne(Topic::class)->latestOfMany();
    }

    public function posts(): HasManyThrough
    {
        return $this->hasManyThrough(Post::class, Topic::class);
    }

    #[Override]
    protected static function booted(): void
    {
        static::deleting(function (Forum $forum): void {
            $forum->topics()->each(function (Topic $topic): void {
                $topic->delete();
            });
        });
    }
}
