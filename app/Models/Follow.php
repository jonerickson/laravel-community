<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasAuthor;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $created_by
 * @property string $followable_type
 * @property int $followable_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $author
 * @property-read mixed $author_name
 * @property-read User $creator
 * @property-read Model|Eloquent $followable
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follow newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follow newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follow query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follow whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follow whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follow whereFollowableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follow whereFollowableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follow whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follow whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
class Follow extends Model
{
    use HasAuthor;

    protected $fillable = [
        'followable_type',
        'followable_id',
    ];

    public function followable(): MorphTo
    {
        return $this->morphTo();
    }
}
