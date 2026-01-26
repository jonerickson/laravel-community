<?php

declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $viewable_type
 * @property int $viewable_id
 * @property string $fingerprint_id
 * @property int $count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Fingerprint|null $fingerprint
 * @property-read Model|Eloquent $viewable
 *
 * @method static \Database\Factories\ViewFactory factory($count = null, $state = [])
 * @method static Builder<static>|View newModelQuery()
 * @method static Builder<static>|View newQuery()
 * @method static Builder<static>|View query()
 * @method static Builder<static>|View whereCount($value)
 * @method static Builder<static>|View whereCreatedAt($value)
 * @method static Builder<static>|View whereFingerprintId($value)
 * @method static Builder<static>|View whereId($value)
 * @method static Builder<static>|View whereUpdatedAt($value)
 * @method static Builder<static>|View whereViewableId($value)
 * @method static Builder<static>|View whereViewableType($value)
 *
 * @mixin Eloquent
 */
class View extends Model
{
    use HasFactory;

    protected $attributes = [
        'count' => 1,
    ];

    protected $fillable = [
        'viewable_type',
        'viewable_id',
        'fingerprint_id',
        'count',
    ];

    public function viewable(): MorphTo
    {
        return $this->morphTo();
    }

    public function fingerprint(): BelongsTo
    {
        return $this->belongsTo(Fingerprint::class, 'fingerprint_id', 'fingerprint_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'count' => 'integer',
        ];
    }
}
