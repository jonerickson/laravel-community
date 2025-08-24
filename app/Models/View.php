<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasAuthor;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $viewable_type
 * @property int $viewable_id
 * @property int $created_by
 * @property int $count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $author
 * @property-read mixed $author_name
 * @property-read User $creator
 * @property-read Model|Eloquent $viewable
 *
 * @method static Builder<static>|View newModelQuery()
 * @method static Builder<static>|View newQuery()
 * @method static Builder<static>|View query()
 * @method static Builder<static>|View whereCount($value)
 * @method static Builder<static>|View whereCreatedAt($value)
 * @method static Builder<static>|View whereCreatedBy($value)
 * @method static Builder<static>|View whereId($value)
 * @method static Builder<static>|View whereUpdatedAt($value)
 * @method static Builder<static>|View whereViewableId($value)
 * @method static Builder<static>|View whereViewableType($value)
 *
 * @mixin Eloquent
 */
class View extends Model
{
    use HasAuthor;
    use HasFactory;

    protected $fillable = [
        'viewable_type',
        'viewable_id',
    ];

    public function viewable(): MorphTo
    {
        return $this->morphTo();
    }
}
