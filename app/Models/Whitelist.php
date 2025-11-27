<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FilterType;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string|null $content
 * @property string|null $description
 * @property FilterType $filter
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $resource_type
 * @property int|null $resource_id
 * @property-read Model|Eloquent|null $resource
 *
 * @method static \Database\Factories\WhitelistFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Whitelist newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Whitelist newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Whitelist query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Whitelist whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Whitelist whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Whitelist whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Whitelist whereFilter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Whitelist whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Whitelist whereResourceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Whitelist whereResourceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Whitelist whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
class Whitelist extends Model
{
    use HasFactory;

    protected $table = 'whitelist';

    protected $attributes = [
        'filter' => FilterType::String,
    ];

    protected $fillable = [
        'content',
        'description',
        'filter',
        'resource_id',
        'resource_type',
    ];

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
        ];
    }
}
