<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FilterType;
use App\Events\BlacklistCreated;
use App\Events\BlacklistDeleted;
use App\Events\BlacklistUpdated;
use App\Traits\HasAuthor;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string|null $content
 * @property string|null $description
 * @property FilterType $filter
 * @property bool $is_regex
 * @property int|null $warning_id
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $resource_type
 * @property int|null $resource_id
 * @property-read User|null $author
 * @property-read mixed $author_name
 * @property-read User|null $creator
 * @property-read Model|Eloquent|null $resource
 * @property-read Warning|null $warning
 *
 * @method static \Database\Factories\BlacklistFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Blacklist newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Blacklist newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Blacklist query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Blacklist whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Blacklist whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Blacklist whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Blacklist whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Blacklist whereFilter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Blacklist whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Blacklist whereIsRegex($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Blacklist whereResourceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Blacklist whereResourceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Blacklist whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Blacklist whereWarningId($value)
 *
 * @mixin Eloquent
 */
class Blacklist extends Model
{
    use HasAuthor;
    use HasFactory;

    protected $table = 'blacklist';

    protected $attributes = [
        'filter' => FilterType::String,
        'is_regex' => false,
    ];

    protected $fillable = [
        'content',
        'description',
        'filter',
        'is_regex',
        'warning_id',
        'resource_id',
        'resource_type',
    ];

    protected $dispatchesEvents = [
        'created' => BlacklistCreated::class,
        'updated' => BlacklistUpdated::class,
        'deleting' => BlacklistDeleted::class,
    ];

    public function warning(): BelongsTo
    {
        return $this->belongsTo(Warning::class);
    }

    public function resource(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'filter' => FilterType::class,
            'is_regex' => 'boolean',
        ];
    }
}
