<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Sluggable;
use App\Traits\Activateable;
use App\Traits\HasSlug;
use App\Traits\Orderable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int $order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Policy> $activePolicies
 * @property-read int|null $active_policies_count
 * @property-read Collection<int, Policy> $policies
 * @property-read int|null $policies_count
 *
 * @method static Builder<static>|PolicyCategory active()
 * @method static \Database\Factories\PolicyCategoryFactory factory($count = null, $state = [])
 * @method static Builder<static>|PolicyCategory inactive()
 * @method static Builder<static>|PolicyCategory newModelQuery()
 * @method static Builder<static>|PolicyCategory newQuery()
 * @method static Builder<static>|PolicyCategory ordered()
 * @method static Builder<static>|PolicyCategory query()
 * @method static Builder<static>|PolicyCategory whereCreatedAt($value)
 * @method static Builder<static>|PolicyCategory whereDescription($value)
 * @method static Builder<static>|PolicyCategory whereId($value)
 * @method static Builder<static>|PolicyCategory whereIsActive($value)
 * @method static Builder<static>|PolicyCategory whereName($value)
 * @method static Builder<static>|PolicyCategory whereOrder($value)
 * @method static Builder<static>|PolicyCategory whereSlug($value)
 * @method static Builder<static>|PolicyCategory whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PolicyCategory extends Model implements Sluggable
{
    use Activateable;
    use HasFactory;
    use HasSlug;
    use Orderable;

    protected $table = 'policies_categories';

    protected $fillable = [
        'name',
        'description',
    ];

    public function generateSlug(): ?string
    {
        return Str::slug($this->name);
    }

    public function policies(): HasMany
    {
        return $this->hasMany(Policy::class)->ordered();
    }

    public function activePolicies(): HasMany
    {
        return $this
            ->policies()
            ->active();
    }
}
