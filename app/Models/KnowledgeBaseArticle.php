<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Sluggable;
use App\Enums\KnowledgeBaseArticleType;
use App\Traits\HasAuthor;
use App\Traits\HasFeaturedImage;
use App\Traits\HasLogging;
use App\Traits\HasMetadata;
use App\Traits\HasSlug;
use App\Traits\HasUrl;
use App\Traits\Publishable;
use App\Traits\Searchable;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property string|null $excerpt
 * @property string $content
 * @property KnowledgeBaseArticleType $type
 * @property int|null $category_id
 * @property string|null $featured_image
 * @property array<array-key, mixed>|null $metadata
 * @property bool $is_published
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \App\Enums\PublishableStatus $status
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read User|null $author
 * @property-read mixed $author_name
 * @property-read KnowledgeBaseCategory|null $category
 * @property-read User|null $creator
 * @property-read string|null $featured_image_url
 * @property-read int $reading_time
 * @property-read string|null $url
 *
 * @method static \Database\Factories\KnowledgeBaseArticleFactory factory($count = null, $state = [])
 * @method static Builder<static>|KnowledgeBaseArticle inCategory(?int $categoryId)
 * @method static Builder<static>|KnowledgeBaseArticle newModelQuery()
 * @method static Builder<static>|KnowledgeBaseArticle newQuery()
 * @method static Builder<static>|KnowledgeBaseArticle ofType(\App\Enums\KnowledgeBaseArticleType $type)
 * @method static Builder<static>|KnowledgeBaseArticle published()
 * @method static Builder<static>|KnowledgeBaseArticle query()
 * @method static Builder<static>|KnowledgeBaseArticle recent()
 * @method static Builder<static>|KnowledgeBaseArticle unpublished()
 * @method static Builder<static>|KnowledgeBaseArticle whereCategoryId($value)
 * @method static Builder<static>|KnowledgeBaseArticle whereContent($value)
 * @method static Builder<static>|KnowledgeBaseArticle whereCreatedAt($value)
 * @method static Builder<static>|KnowledgeBaseArticle whereCreatedBy($value)
 * @method static Builder<static>|KnowledgeBaseArticle whereExcerpt($value)
 * @method static Builder<static>|KnowledgeBaseArticle whereFeaturedImage($value)
 * @method static Builder<static>|KnowledgeBaseArticle whereId($value)
 * @method static Builder<static>|KnowledgeBaseArticle whereIsPublished($value)
 * @method static Builder<static>|KnowledgeBaseArticle whereMetadata($value)
 * @method static Builder<static>|KnowledgeBaseArticle wherePublishedAt($value)
 * @method static Builder<static>|KnowledgeBaseArticle whereSlug($value)
 * @method static Builder<static>|KnowledgeBaseArticle whereTitle($value)
 * @method static Builder<static>|KnowledgeBaseArticle whereType($value)
 * @method static Builder<static>|KnowledgeBaseArticle whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class KnowledgeBaseArticle extends Model implements HasLabel, Sluggable
{
    use HasAuthor;
    use HasFactory;
    use HasFeaturedImage;
    use HasLogging;
    use HasMetadata;
    use HasSlug;
    use HasUrl;
    use Publishable;
    use Searchable;

    protected $fillable = [
        'title',
        'excerpt',
        'content',
        'type',
        'category_id',
    ];

    protected $appends = [
        'reading_time',
    ];

    public function generateSlug(): ?string
    {
        return $this->title;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBaseCategory::class, 'category_id');
    }

    public function scopeOfType(Builder $query, KnowledgeBaseArticleType $type): void
    {
        $query->where('type', $type);
    }

    public function scopeInCategory(Builder $query, ?int $categoryId): void
    {
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
    }

    public function getUrl(): ?string
    {
        return route('knowledge-base.show', $this);
    }

    /**
     * @return array<string, int|string|null>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => Str::of($this->content)->stripTags()->toString(),
            'type' => $this->type->value,
            'category' => $this->category?->name,
            'author' => $this->author?->name,
            'created_at' => $this->created_at?->toDateTimeString() ?? '',
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return $this->is_published;
    }

    /**
     * @return array<int, string>
     */
    public function getLoggedAttributes(): array
    {
        return [
            'title',
            'content',
            'type',
            'category_id',
            'is_published',
            'published_at',
        ];
    }

    public function getActivityDescription(string $eventName): string
    {
        return sprintf('Knowledge base article "%s" %s', $this->title, $eventName);
    }

    public function getActivityLogName(): string
    {
        return 'knowledge_base';
    }

    public function readingTime(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                $wordCount = Str::of($this->content)->stripTags()->wordCount();

                return max(1, (int) ceil($wordCount / 200));
            }
        )->shouldCache();
    }

    public function getLabel(): string|Htmlable|null
    {
        return $this->title;
    }

    protected function casts(): array
    {
        return [
            'type' => KnowledgeBaseArticleType::class,
        ];
    }
}
