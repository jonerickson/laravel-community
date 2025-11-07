<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FieldType;
use App\Traits\Orderable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $label
 * @property FieldType $type
 * @property string|null $description
 * @property array<array-key, mixed>|null $options
 * @property bool $is_required
 * @property bool $is_public
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users
 * @property-read int|null $users_count
 *
 * @method static \Database\Factories\FieldFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Field newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Field newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Field ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Field query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Field whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Field whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Field whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Field whereIsPublic($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Field whereIsRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Field whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Field whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Field whereOptions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Field whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Field whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Field whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Field extends Model
{
    use HasFactory;
    use Orderable;

    protected $attributes = [
        'type' => FieldType::Text,
    ];

    protected $fillable = [
        'name',
        'label',
        'type',
        'description',
        'options',
        'is_required',
        'is_public',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'users_fields')
            ->withPivot('value')
            ->withTimestamps();
    }

    protected function casts(): array
    {
        return [
            'type' => FieldType::class,
            'options' => 'array',
            'is_required' => 'boolean',
            'is_public' => 'boolean',
            'order' => 'integer',
        ];
    }
}
