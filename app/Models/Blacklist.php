<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $content
 * @property string|null $description
 * @property bool $is_regex
 * @property int|null $warning_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Warning|null $warning
 *
 * @method static \Database\Factories\BlacklistFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Blacklist newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Blacklist newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Blacklist query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Blacklist whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Blacklist whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Blacklist whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Blacklist whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Blacklist whereIsRegex($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Blacklist whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Blacklist whereWarningId($value)
 *
 * @mixin \Eloquent
 */
class Blacklist extends Model
{
    use HasFactory;

    protected $table = 'blacklist';

    protected $fillable = [
        'content',
        'description',
        'is_regex',
        'warning_id',
    ];

    protected $attributes = [
        'is_regex' => false,
    ];

    public function warning(): BelongsTo
    {
        return $this->belongsTo(Warning::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_regex' => 'boolean',
        ];
    }
}
