<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Sluggable;
use App\Traits\HasAuthor;
use App\Traits\HasOrder;
use App\Traits\HasSlug;
use App\Traits\HasUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $version
 * @property string $content
 * @property int $policy_category_id
 * @property int $order
 * @property bool $is_active
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $effective_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $author
 * @property-read mixed $author_name
 * @property-read PolicyCategory $category
 * @property-read User $creator
 * @property-read string|null $url
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy effective()
 * @method static \Database\Factories\PolicyFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereEffectiveDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy wherePolicyCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereVersion($value)
 *
 * @mixin \Eloquent
 */
class Policy extends Model implements Sluggable
{
    use HasAuthor;
    use HasFactory;
    use HasOrder;
    use HasSlug;
    use HasUrl;
    use Searchable;

    protected $fillable = [
        'title',
        'content',
        'version',
        'policy_category_id',
        'is_active',
        'effective_date',
    ];

    public function generateSlug(): string
    {
        return Str::slug($this->title);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PolicyCategory::class, 'policy_category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeEffective($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('effective_date')
                ->orWhere('effective_date', '<=', now());
        });
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => strip_tags($this->content ?? ''),
            'version' => $this->version,
            //            'category_name' => $this->category?->name ?? '',
            //            'author_name' => $this->author?->name ?? '',
            'effective_date' => $this->effective_date?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString() ?? '',
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return $this->is_active && ($this->effective_date === null || $this->effective_date->isPast());
    }

    public function getUrl(): ?string
    {
        return route('policies.show', [$this->category->slug, $this->slug]);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'effective_date' => 'datetime',
        ];
    }
}
