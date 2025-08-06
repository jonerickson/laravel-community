<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Sluggable;
use App\Traits\HasOrder;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int $order
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Policy> $activePolicies
 * @property-read int|null $active_policies_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Policy> $policies
 * @property-read int|null $policies_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyCategory active()
 * @method static \Database\Factories\PolicyCategoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyCategory ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyCategory whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyCategory whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyCategory whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyCategory whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyCategory whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PolicyCategory extends Model implements Sluggable
{
    use HasFactory;
    use HasOrder;
    use HasSlug;

    protected $table = 'policies_categories';

    protected $fillable = [
        'name',
        'description',
        'is_active',
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
        return $this->policies()->where('is_active', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
