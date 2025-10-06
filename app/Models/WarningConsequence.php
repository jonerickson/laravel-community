<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WarningConsequenceType;
use App\Traits\Activateable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property WarningConsequenceType $type
 * @property int $threshold
 * @property int $duration_days
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarningConsequence active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarningConsequence inactive()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarningConsequence newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarningConsequence newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarningConsequence query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarningConsequence whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarningConsequence whereDurationDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarningConsequence whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarningConsequence whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarningConsequence whereThreshold($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarningConsequence whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarningConsequence whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class WarningConsequence extends Model
{
    use Activateable;

    protected $table = 'warnings_consequences';

    protected $fillable = [
        'type',
        'threshold',
        'duration_days',
    ];

    protected function casts(): array
    {
        return [
            'type' => WarningConsequenceType::class,
            'threshold' => 'integer',
            'duration_days' => 'integer',
        ];
    }
}
