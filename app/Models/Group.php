<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasFiles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Traits\HasPermissions;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $image
 * @property string $color
 * @property int $order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read File|null $file
 * @property-read Collection<int, File> $files
 * @property-read int|null $files_count
 * @property-read Collection<int, \App\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read Collection<int, User> $users
 * @property-read int|null $users_count
 *
 * @method static Builder<static>|Group active()
 * @method static \Database\Factories\GroupFactory factory($count = null, $state = [])
 * @method static Builder<static>|Group newModelQuery()
 * @method static Builder<static>|Group newQuery()
 * @method static Builder<static>|Group ordered()
 * @method static Builder<static>|Group permission($permissions, $without = false)
 * @method static Builder<static>|Group query()
 * @method static Builder<static>|Group whereColor($value)
 * @method static Builder<static>|Group whereCreatedAt($value)
 * @method static Builder<static>|Group whereDescription($value)
 * @method static Builder<static>|Group whereId($value)
 * @method static Builder<static>|Group whereImage($value)
 * @method static Builder<static>|Group whereIsActive($value)
 * @method static Builder<static>|Group whereName($value)
 * @method static Builder<static>|Group whereOrder($value)
 * @method static Builder<static>|Group whereUpdatedAt($value)
 * @method static Builder<static>|Group withoutPermission($permissions)
 *
 * @mixin \Eloquent
 */
class Group extends Model
{
    use HasFactory;
    use HasFiles;
    use HasPermissions;

    protected $fillable = [
        'name',
        'description',
        'image',
        'color',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'users_groups');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }
}
