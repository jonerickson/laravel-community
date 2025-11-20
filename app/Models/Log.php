<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\HttpMethod;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $endpoint
 * @property HttpMethod $method
 * @property string|null $status
 * @property array<array-key, mixed>|null $request_body
 * @property array<array-key, mixed>|null $request_headers
 * @property array<array-key, mixed>|null $response_content
 * @property array<array-key, mixed>|null $response_headers
 * @property string|null $loggable_type
 * @property int|null $loggable_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model|Eloquent|null $loggable
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Log newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Log newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Log query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Log whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Log whereEndpoint($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Log whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Log whereLoggableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Log whereLoggableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Log whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Log whereRequestBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Log whereRequestHeaders($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Log whereResponseContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Log whereResponseHeaders($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Log whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Log whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
class Log extends Model
{
    protected $fillable = [
        'endpoint',
        'method',
        'status',
        'request_body',
        'request_headers',
        'response_content',
        'response_headers',
    ];

    public function loggable(): MorphTo
    {
        return $this->morphTo('loggable');
    }

    protected function casts(): array
    {
        return [
            'method' => HttpMethod::class,
            'request_body' => 'json',
            'request_headers' => 'array',
            'response_content' => 'json',
            'response_headers' => 'array',
        ];
    }
}
