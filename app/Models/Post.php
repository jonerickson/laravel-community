<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Sluggable;
use App\Enums\PostType;
use App\Enums\WarningConsequenceType;
use App\Events\PostCreated;
use App\Traits\Approvable;
use App\Traits\Commentable;
use App\Traits\Featureable;
use App\Traits\HasAuthor;
use App\Traits\HasFeaturedImage;
use App\Traits\HasLogging;
use App\Traits\HasMetadata;
use App\Traits\HasSlug;
use App\Traits\HasUrl;
use App\Traits\Likeable;
use App\Traits\Pinnable;
use App\Traits\Publishable;
use App\Traits\Readable;
use App\Traits\Reportable;
use App\Traits\Viewable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Override;

/**
 * @property int $id
 * @property PostType $type
 * @property string $title
 * @property string|null $slug
 * @property string|null $excerpt
 * @property string $content
 * @property bool $is_published
 * @property bool $is_approved
 * @property bool $is_featured
 * @property bool $is_pinned
 * @property bool $comments_enabled
 * @property int|null $topic_id
 * @property string|null $featured_image
 * @property array<array-key, mixed>|null $metadata
 * @property Carbon|null $published_at
 * @property int $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property \App\Enums\PublishableStatus $status
 * @property-read Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read Collection<int, Comment> $approvedComments
 * @property-read int|null $approved_comments_count
 * @property-read Collection<int, Report> $approvedReports
 * @property-read int|null $approved_reports_count
 * @property-read User $author
 * @property-read mixed $author_name
 * @property-read Collection<int, Comment> $comments
 * @property-read int $comments_count
 * @property-read User $creator
 * @property-read string|null $featured_image_url
 * @property-read bool $is_read_by_user
 * @property-read bool $is_reported
 * @property-read Comment|null $latestComment
 * @property-read Collection<int, Like> $likes
 * @property-read int $likes_count
 * @property-read array $likes_summary
 * @property-read Collection<int, Report> $pendingReports
 * @property-read int|null $pending_reports_count
 * @property-read int $reading_time
 * @property-read Collection<int, Read> $reads
 * @property-read int $reads_count
 * @property-read Collection<int, Report> $rejectedReports
 * @property-read int|null $rejected_reports_count
 * @property-read int $report_count
 * @property-read Collection<int, Report> $reports
 * @property-read int|null $reports_count
 * @property-read Collection<int, Comment> $topLevelComments
 * @property-read int|null $top_level_comments_count
 * @property-read Topic|null $topic
 * @property-read int $unique_views_count
 * @property-read string|null $url
 * @property-read string|null $user_reaction
 * @property-read array $user_reactions
 * @property-read Collection<int, View> $views
 * @property-read string|int $views_count
 *
 * @method static Builder<static>|Post approved()
 * @method static Builder<static>|Post blog()
 * @method static \Database\Factories\PostFactory factory($count = null, $state = [])
 * @method static Builder<static>|Post featured()
 * @method static Builder<static>|Post forum()
 * @method static Builder<static>|Post latestActivity()
 * @method static Builder<static>|Post needingModeration()
 * @method static Builder<static>|Post newModelQuery()
 * @method static Builder<static>|Post newQuery()
 * @method static Builder<static>|Post notFeatured()
 * @method static Builder<static>|Post notPinned()
 * @method static Builder<static>|Post pinned()
 * @method static Builder<static>|Post published()
 * @method static Builder<static>|Post query()
 * @method static Builder<static>|Post read(?\App\Models\User $user = null)
 * @method static Builder<static>|Post recent()
 * @method static Builder<static>|Post unapproved()
 * @method static Builder<static>|Post unpublished()
 * @method static Builder<static>|Post unread(?\App\Models\User $user = null)
 * @method static Builder<static>|Post whereCommentsEnabled($value)
 * @method static Builder<static>|Post whereContent($value)
 * @method static Builder<static>|Post whereCreatedAt($value)
 * @method static Builder<static>|Post whereCreatedBy($value)
 * @method static Builder<static>|Post whereExcerpt($value)
 * @method static Builder<static>|Post whereFeaturedImage($value)
 * @method static Builder<static>|Post whereId($value)
 * @method static Builder<static>|Post whereIsApproved($value)
 * @method static Builder<static>|Post whereIsFeatured($value)
 * @method static Builder<static>|Post whereIsPinned($value)
 * @method static Builder<static>|Post whereIsPublished($value)
 * @method static Builder<static>|Post whereMetadata($value)
 * @method static Builder<static>|Post wherePublishedAt($value)
 * @method static Builder<static>|Post whereSlug($value)
 * @method static Builder<static>|Post whereTitle($value)
 * @method static Builder<static>|Post whereTopicId($value)
 * @method static Builder<static>|Post whereType($value)
 * @method static Builder<static>|Post whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Post extends Model implements Sluggable
{
    use Approvable;
    use Commentable;
    use Featureable;
    use HasAuthor;
    use HasFactory;
    use HasFeaturedImage;
    use HasLogging;
    use HasMetadata;
    use HasSlug;
    use HasUrl;
    use Likeable;
    use Pinnable;
    use Publishable;
    use Readable;
    use Reportable;
    use Searchable;
    use Viewable;

    protected $fillable = [
        'type',
        'topic_id',
        'title',
        'excerpt',
        'content',
    ];

    protected $touches = [
        'topic',
    ];

    protected $appends = [
        'reading_time',
    ];

    protected $dispatchesEvents = [
        'created' => PostCreated::class,
    ];

    public function generateSlug(): ?string
    {
        return match ($this->type) {
            PostType::Blog => Str::slug($this->title),
            PostType::Forum => Str::of($this->content)->stripTags()->limit(20)->slug()->toString(),
        };
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function scopeBlog(Builder $query): void
    {
        $query->where('type', PostType::Blog);
    }

    public function scopeForum(Builder $query): void
    {
        $query->where('type', PostType::Forum);
    }

    public function scopeLatestActivity(Builder $query): void
    {
        $query->orderByDesc('is_pinned')
            ->orderBy('created_at');
    }

    public function scopeNeedingModeration(Builder $query): void
    {
        $query->where(function (Builder $query): void {
            $query
                ->unpublished()
                ->orWhereHas('pendingReports')
                ->orWhere(fn (Builder $query) => $query->unapproved());
        })
            ->with(['author', 'pendingReports'])
            ->withCount('pendingReports')
            ->latest();
    }

    public function getUrl(): ?string
    {
        return match ($this->type) {
            PostType::Blog => route('blog.show', $this),
            PostType::Forum => $this->topic
                ? route('forums.topics.show', [$this->topic->forum, $this->topic]).'#post-'.$this->id
                : null,
        };
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => strip_tags($this->content ?? ''),
            'excerpt' => $this->excerpt,
            'type' => $this->type->value ?? '',
            'created_at' => $this->created_at?->toDateTimeString() ?? '',
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return $this->is_published;
    }

    public function getLoggedAttributes(): array
    {
        return [
            'type',
            'title',
            'content',
            'is_published',
            'is_featured',
            'is_pinned',
            'published_at',
            'topic_id',
        ];
    }

    public function getActivityDescription(string $eventName): string
    {
        $type = $this->type?->value ?? 'post';
        $title = $this->title ? " \"{$this->title}\"" : '';

        return ucfirst($type).$title." $eventName";
    }

    public function getActivityLogName(): string
    {
        return match ($this->type) {
            PostType::Blog => 'blog',
            PostType::Forum => 'forum',
        };
    }

    public function readingTime(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                $wordCount = str_word_count(strip_tags($this->content));

                return max(1, (int) ceil($wordCount / 200));
            }
        )->shouldCache();
    }

    #[Override]
    protected static function booted(): void
    {
        static::creating(function (Post $post): void {
            if ($author = $post->author) {
                $requiresModeration = $author->active_consequence?->type === WarningConsequenceType::ModerateContent;

                $post->forceFill([
                    'is_approved' => ! $requiresModeration,
                ]);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'type' => PostType::class,
        ];
    }
}
