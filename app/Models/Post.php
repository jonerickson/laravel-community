<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Sluggable;
use App\Enums\PostType;
use App\Traits\HasAuthor;
use App\Traits\HasComments;
use App\Traits\HasFeaturedImage;
use App\Traits\HasLikes;
use App\Traits\HasLogging;
use App\Traits\HasMetadata;
use App\Traits\HasSlug;
use App\Traits\HasUrl;
use App\Traits\Reportable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property PostType $type
 * @property string $title
 * @property string|null $slug
 * @property string|null $excerpt
 * @property string $content
 * @property bool $is_published
 * @property bool $is_featured
 * @property bool $comments_enabled
 * @property int|null $topic_id
 * @property string|null $featured_image
 * @property array<array-key, mixed>|null $metadata
 * @property Carbon|null $published_at
 * @property int $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
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
 * @property-read bool $is_reported
 * @property-read Collection<int, Like> $likes
 * @property-read int $likes_count
 * @property-read array $likes_summary
 * @property-read Collection<int, Report> $pendingReports
 * @property-read int|null $pending_reports_count
 * @property-read int $reading_time
 * @property-read Collection<int, Report> $rejectedReports
 * @property-read int|null $rejected_reports_count
 * @property-read Collection<int, Report> $reports
 * @property-read int|null $reports_count
 * @property-read Collection<int, Comment> $topLevelComments
 * @property-read int|null $top_level_comments_count
 * @property-read Topic|null $topic
 * @property-read string|null $url
 * @property-read string|null $user_reaction
 * @property-read array $user_reactions
 *
 * @method static Builder<static>|Post blog()
 * @method static \Database\Factories\PostFactory factory($count = null, $state = [])
 * @method static Builder<static>|Post featured()
 * @method static Builder<static>|Post forum()
 * @method static Builder<static>|Post newModelQuery()
 * @method static Builder<static>|Post newQuery()
 * @method static Builder<static>|Post published()
 * @method static Builder<static>|Post query()
 * @method static Builder<static>|Post recent()
 * @method static Builder<static>|Post whereCommentsEnabled($value)
 * @method static Builder<static>|Post whereContent($value)
 * @method static Builder<static>|Post whereCreatedAt($value)
 * @method static Builder<static>|Post whereCreatedBy($value)
 * @method static Builder<static>|Post whereExcerpt($value)
 * @method static Builder<static>|Post whereFeaturedImage($value)
 * @method static Builder<static>|Post whereId($value)
 * @method static Builder<static>|Post whereIsFeatured($value)
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
    use HasAuthor;
    use HasComments;
    use HasFactory;
    use HasFeaturedImage;
    use HasLikes;
    use HasLogging;
    use HasMetadata;
    use HasSlug;
    use HasUrl;
    use Reportable;
    use Searchable;

    protected $fillable = [
        'type',
        'topic_id',
        'title',
        'excerpt',
        'content',
        'is_published',
        'is_featured',
        'published_at',
    ];

    protected $touches = [
        'topic',
    ];

    protected $appends = [
        'reading_time',
    ];

    public function generateSlug(): ?string
    {
        return match ($this->type) {
            PostType::Blog => Str::slug($this->title),
            PostType::Forum => null,
        };
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('published_at', 'desc');
    }

    public function scopeBlog($query)
    {
        return $query->where('type', PostType::Blog);
    }

    public function scopeForum($query)
    {
        return $query->where('type', PostType::Forum);
    }

    public function isPublished(): bool
    {
        return $this->is_published
               && $this->published_at !== null
               && $this->published_at->isPast();
    }

    public function getUrl(): ?string
    {
        return match ($this->type) {
            PostType::Blog => route('blog.show', $this),
            PostType::Forum => null,
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
            'published_at',
            'topic_id',
        ];
    }

    public function getActivityDescription(string $eventName): string
    {
        $type = $this->type?->value ?? 'post';
        $title = $this->title ? " \"{$this->title}\"" : '';

        return ucfirst($type).$title." {$eventName}";
    }

    public function getActivityLogName(): string
    {
        return match ($this->type) {
            PostType::Blog => 'blog',
            PostType::Forum => 'forum',
            default => 'post',
        };
    }

    protected function readingTime(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                $wordCount = str_word_count(strip_tags($this->content));

                return max(1, (int) ceil($wordCount / 200));
            }
        )->shouldCache();
    }

    protected function casts(): array
    {
        return [
            'type' => PostType::class,
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }
}
