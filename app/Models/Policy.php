<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Sluggable;
use App\Traits\Activateable;
use App\Traits\HasAuthor;
use App\Traits\HasSlug;
use App\Traits\HasUrl;
use App\Traits\Orderable;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $version
 * @property string|null $description
 * @property string $content
 * @property int $policy_category_id
 * @property int $order
 * @property bool $is_active
 * @property Carbon|null $effective_at
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $author
 * @property-read mixed $author_name
 * @property-read PolicyCategory $category
 * @property-read User|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Product> $products
 * @property-read int|null $products_count
 * @property-read string|null $url
 *
 * @method static Builder<static>|Policy active()
 * @method static Builder<static>|Policy effective()
 * @method static \Database\Factories\PolicyFactory factory($count = null, $state = [])
 * @method static Builder<static>|Policy inactive()
 * @method static Builder<static>|Policy newModelQuery()
 * @method static Builder<static>|Policy newQuery()
 * @method static Builder<static>|Policy ordered()
 * @method static Builder<static>|Policy query()
 * @method static Builder<static>|Policy whereContent($value)
 * @method static Builder<static>|Policy whereCreatedAt($value)
 * @method static Builder<static>|Policy whereCreatedBy($value)
 * @method static Builder<static>|Policy whereDescription($value)
 * @method static Builder<static>|Policy whereEffectiveAt($value)
 * @method static Builder<static>|Policy whereId($value)
 * @method static Builder<static>|Policy whereIsActive($value)
 * @method static Builder<static>|Policy whereOrder($value)
 * @method static Builder<static>|Policy wherePolicyCategoryId($value)
 * @method static Builder<static>|Policy whereSlug($value)
 * @method static Builder<static>|Policy whereTitle($value)
 * @method static Builder<static>|Policy whereUpdatedAt($value)
 * @method static Builder<static>|Policy whereVersion($value)
 *
 * @mixin \Eloquent
 */
class Policy extends Model implements Sluggable
{
    use Activateable;
    use HasAuthor;
    use HasFactory;
    use HasSlug;
    use HasUrl;
    use Orderable;
    use Searchable;

    protected $fillable = [
        'title',
        'description',
        'content',
        'version',
        'policy_category_id',
        'effective_at',
    ];

    public function generateSlug(): ?string
    {
        return Str::slug($this->title);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PolicyCategory::class, 'policy_category_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'policies_products');
    }

    public function scopeEffective(Builder $query): void
    {
        $query->where(function (Builder $query): void {
            $query->whereNull('effective_at')
                ->orWhere('effective_at', '<=', now());
        });
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
            'version' => $this->version,
            'category' => $this->category?->name,
            'effective_at' => $this->effective_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString() ?? '',
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return Gate::check('view', $this);
    }

    public function getUrl(): ?string
    {
        return route('policies.show', [$this->category->slug, $this->slug]);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'effective_at' => 'datetime',
        ];
    }
}
