<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasAuthor;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property-read User|null $author
 * @property-read mixed $author_name
 * @property-read User|null $creator
 * @property-read Model|Eloquent $notable
 *
 * @method static \Database\Factories\NoteFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note query()
 *
 * @mixin \Eloquent
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

    public function notable(): MorphTo
    {
        return $this->morphTo();
    }
}
