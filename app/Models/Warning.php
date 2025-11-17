<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Activateable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $points
 * @property int $days_applied
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserWarning> $userWarnings
 * @property-read int|null $user_warnings_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning inactive()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning whereDaysApplied($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning wherePoints($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Warning extends Model
{
    use Activateable;

    protected $fillable = [
        'name',
        'description',
        'points',
        'days_applied',
    ];

    public function userWarnings(): HasMany
    {
        return $this->hasMany(UserWarning::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'days_applied' => 'integer',
        ];
    }
}
