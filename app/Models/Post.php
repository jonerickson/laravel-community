<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Sluggable;
use App\Enums\PostType;
use App\Traits\HasAuthor;
use App\Traits\HasComments;
use App\Traits\HasFeaturedImage;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property PostType $type
 * @property string $title
 * @property string $slug
 * @property string|null $excerpt
 * @property string $content
 * @property string|null $featured_image
 * @property bool $is_published
 * @property bool $is_featured
 * @property int|null $topic_id
 * @property array<array-key, mixed>|null $meta
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Comment> $approvedComments
 * @property-read int|null $approved_comments_count
 * @property-read User $author
 * @property-read mixed $author_name
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Comment> $comments
 * @property-read int $comments_count
 * @property-read User $creator
 * @property-read int $reading_time
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Comment> $topLevelComments
 * @property-read int|null $top_level_comments_count
 * @property-read Topic|null $topic
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post blog()
 * @method static \Database\Factories\PostFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post featured()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post forum()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post published()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post recent()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereExcerpt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereFeaturedImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereIsFeatured($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereIsPublished($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereTopicId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Post extends Model implements Sluggable
{
    use HasAuthor;
    use HasComments;
    use HasFactory;
    use HasFeaturedImage;
    use HasSlug;

    protected $fillable = [
        'type',
        'topic_id',
        'title',
        'excerpt',
        'content',
        'featured_image',
        'is_published',
        'is_featured',
        'published_at',
        'created_by',
        'meta',
    ];

    protected $touches = [
        'topic',
    ];

    public function generateSlug(): string
    {
        return Str::slug($this->title);
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

    protected function readingTime(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                $wordCount = str_word_count(strip_tags($this->content));

                return max(1, (int) ceil($wordCount / 200));
            }
        );
    }

    protected function casts(): array
    {
        return [
            'type' => PostType::class,
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'meta' => 'array',
        ];
    }
}
