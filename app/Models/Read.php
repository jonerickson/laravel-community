<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasAuthor;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $readable_type
 * @property int $readable_id
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $author
 * @property-read mixed $author_name
 * @property-read User $creator
 * @property-read Model|Eloquent $readable
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Read newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Read newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Read query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Read whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Read whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Read whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Read whereReadableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Read whereReadableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Read whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
class Read extends Model
{
    use HasAuthor;
    use HasFactory;

    protected $fillable = [
        'readable_type',
        'readable_id',
        'created_by',
    ];

    public function readable(): MorphTo
    {
        return $this->morphTo();
    }
}
