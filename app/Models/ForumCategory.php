<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Sluggable;
use App\Events\ForumCategoryCreated;
use App\Events\ForumCategoryDeleted;
use App\Events\ForumCategoryUpdated;
use App\Traits\Activateable;
use App\Traits\HasColor;
use App\Traits\HasFeaturedImage;
use App\Traits\HasForumPermissions;
use App\Traits\HasGroups;
use App\Traits\HasIcon;
use App\Traits\HasSlug;
use App\Traits\Orderable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $featured_image
 * @property string|null $description
 * @property string|null $icon
 * @property string|null $color
 * @property int $order
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Data\GroupStyleData|null $display_style
 * @property-read string|null $featured_image_url
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Forum> $forums
 * @property-read int|null $forums_count
 * @property-read ForumCategoryGroup|null $pivot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Group> $groups
 * @property-read int|null $groups_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Topic> $topics
 * @property-read int|null $topics_count
 *
 * @method static Builder<static>|ForumCategory active()
 * @method static \Database\Factories\ForumCategoryFactory factory($count = null, $state = [])
 * @method static Builder<static>|ForumCategory inactive()
 * @method static Builder<static>|ForumCategory newModelQuery()
 * @method static Builder<static>|ForumCategory newQuery()
 * @method static Builder<static>|ForumCategory ordered()
 * @method static Builder<static>|ForumCategory query()
 * @method static Builder<static>|ForumCategory readableByUser(?\App\Models\User $user = null)
 * @method static Builder<static>|ForumCategory whereColor($value)
 * @method static Builder<static>|ForumCategory whereCreatedAt($value)
 * @method static Builder<static>|ForumCategory whereDescription($value)
 * @method static Builder<static>|ForumCategory whereFeaturedImage($value)
 * @method static Builder<static>|ForumCategory whereIcon($value)
 * @method static Builder<static>|ForumCategory whereId($value)
 * @method static Builder<static>|ForumCategory whereIsActive($value)
 * @method static Builder<static>|ForumCategory whereName($value)
 * @method static Builder<static>|ForumCategory whereOrder($value)
 * @method static Builder<static>|ForumCategory whereSlug($value)
 * @method static Builder<static>|ForumCategory whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ForumCategory extends Model implements Sluggable
{
    use Activateable;
    use HasColor;
    use HasFactory;
    use HasFeaturedImage;
    use HasForumPermissions;
    use HasGroups;
    use HasIcon;
    use HasSlug;
    use Orderable;

    protected $table = 'forums_categories';

    protected ?string $groupsForeignPivotKey = 'category_id';

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $dispatchesEvents = [
        'created' => ForumCategoryCreated::class,
        'updated' => ForumCategoryUpdated::class,
        'deleting' => ForumCategoryDeleted::class,
    ];

    public function forums(): HasMany
    {
        return $this->hasMany(Forum::class, 'category_id');
    }

    public function topics(): HasManyThrough
    {
        return $this->hasManyThrough(Topic::class, Forum::class);
    }

    public function generateSlug(): ?string
    {
        return Str::slug($this->name);
    }
}
