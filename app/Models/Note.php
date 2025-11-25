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
 * @property string $notable_type
 * @property int $notable_id
 * @property string $content
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $author
 * @property-read mixed $author_name
 * @property-read User $creator
 * @property-read Model|Eloquent $notable
 *
 * @method static \Database\Factories\NoteFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note whereNotableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note whereNotableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
class Note extends Model
{
    use HasAuthor;
    use HasFactory;

    protected $fillable = [
        'content',
        'notable_type',
        'notable_id',
    ];

    protected $touches = [
        'notable',
    ];

    public function notable(): MorphTo
    {
        return $this->morphTo();
    }
}
