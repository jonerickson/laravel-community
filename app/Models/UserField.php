<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $id
 * @property int $user_id
 * @property int $field_id
 * @property string|null $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Field $field
 * @property-read User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserField newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserField newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserField query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserField whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserField whereFieldId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserField whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserField whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserField whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserField whereValue($value)
 *
 * @mixin \Eloquent
 */
class UserField extends Pivot
{
    protected $table = 'users_fields';

    protected $fillable = [
        'user_id',
        'field_id',
        'value',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class);
    }
}
