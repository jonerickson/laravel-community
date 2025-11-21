<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\HttpMethod;
use App\Enums\HttpStatusCode;
use Eloquent;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Context;
use Override;

/**
 * @property int $id
 * @property string $request_id
 * @property string $endpoint
 * @property HttpMethod $method
 * @property HttpStatusCode|null $status
 * @property array<array-key, mixed>|null $request_body
 * @property array<array-key, mixed>|null $request_headers
 * @property array<array-key, mixed>|null $response_content
 * @property array<array-key, mixed>|null $response_headers
 * @property string|null $loggable_type
 * @property int|null $loggable_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model|Eloquent|null $loggable
 * @property-read string|null $type
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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Log whereRequestId($value)
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
        'request_id',
        'endpoint',
        'method',
        'status',
        'request_body',
        'request_headers',
        'response_content',
        'response_headers',
        'loggable_type',
        'loggable_id',
    ];

    protected $appends = [
        'type',
    ];

    public function loggable(): MorphTo
    {
        return $this->morphTo('loggable');
    }

    public function type(): Attribute
    {
        return Attribute::get(fn (): ?string => match (true) {
            $this->loggable instanceof User => 'API',
            $this->loggable instanceof Webhook => 'Webhook',
            default => null,
        });
    }

    #[Override]
    protected static function booted(): void
    {
        static::creating(fn (Log $log) => $log->forceFill(['request_id' => Context::get('request_id')]));
    }

    protected function casts(): array
    {
        return [
            'method' => HttpMethod::class,
            'request_body' => 'json',
            'request_headers' => 'array',
            'response_content' => 'json',
            'response_headers' => 'array',
            'status' => HttpStatusCode::class,
        ];
    }
}
