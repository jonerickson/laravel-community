<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Sluggable;
use App\Traits\Activateable;
use App\Traits\HasColor;
use App\Traits\HasGroups;
use App\Traits\HasIcon;
use App\Traits\HasImages;
use App\Traits\HasSlug;
use App\Traits\Orderable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $icon
 * @property string|null $color
 * @property int $order
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ForumCategory> $children
 * @property-read int|null $children_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Forum> $forums
 * @property-read int|null $forums_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Group> $groups
 * @property-read int|null $groups_count
 * @property-read Image|null $image
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Image> $images
 * @property-read int|null $images_count
 * @property-read ForumCategory|null $parent
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategory active()
 * @method static \Database\Factories\ForumCategoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategory inactive()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategory ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategory whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategory whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategory whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategory whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategory whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategory whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategory whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ForumCategory extends Model implements Sluggable
{
    use Activateable;
    use HasColor;
    use HasFactory;
    use HasGroups;
    use HasIcon;
    use HasImages;
    use HasSlug;
    use Orderable;

    protected $table = 'forums_categories';

    protected $fillable = [
        'parent_id',
        'name',
        'description',
        'is_active',
    ];

    protected ?string $groupsForeignPivotKey = 'category_id';

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ForumCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ForumCategory::class, 'parent_id');
    }

    public function forums(): HasMany
    {
        return $this->hasMany(Forum::class, 'category_id');
    }

    public function generateSlug(): ?string
    {
        return Str::slug($this->name);
    }
}
