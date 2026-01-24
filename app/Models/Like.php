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
 * @property string $likeable_type
 * @property int $likeable_id
 * @property string $emoji
 * @property int $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $author
 * @property-read mixed $author_name
 * @property-read User $creator
 * @property-read Model|Eloquent $likeable
 *
 * @method static \Database\Factories\LikeFactory factory($count = null, $state = [])
 * @method static Builder<static>|Like newModelQuery()
 * @method static Builder<static>|Like newQuery()
 * @method static Builder<static>|Like query()
 * @method static Builder<static>|Like whereCreatedAt($value)
 * @method static Builder<static>|Like whereCreatedBy($value)
 * @method static Builder<static>|Like whereEmoji($value)
 * @method static Builder<static>|Like whereId($value)
 * @method static Builder<static>|Like whereLikeableId($value)
 * @method static Builder<static>|Like whereLikeableType($value)
 * @method static Builder<static>|Like whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
class Like extends Model
{
    use HasAuthor;
    use HasFactory;

    protected $fillable = [
        'likeable_type',
        'likeable_id',
        'emoji',
    ];

    public function likeable(): MorphTo
    {
        return $this->morphTo();
    }
}
