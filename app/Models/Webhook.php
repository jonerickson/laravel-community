<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\HttpMethod;
use App\Enums\RenderEngine;
use App\Traits\Loggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $event
 * @property string $url
 * @property HttpMethod $method
 * @property array<array-key, mixed>|null $headers
 * @property array<array-key, mixed>|null $payload_json
 * @property string|null $payload_text
 * @property RenderEngine $render
 * @property string $secret
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Log> $logs
 * @property-read int|null $logs_count
 *
 * @method static \Database\Factories\WebhookFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook whereEvent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook whereHeaders($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook wherePayloadJson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook wherePayloadText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook whereRender($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook whereSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook whereUrl($value)
 *
 * @mixin \Eloquent
 */
class Webhook extends Model
{
    use HasFactory;
    use Loggable;

    protected $attributes = [
        'render' => RenderEngine::ExpressionLanguage,
    ];

    protected $fillable = [
        'event',
        'url',
        'method',
        'headers',
        'payload_json',
        'payload_text',
        'render',
        'secret',
    ];

    protected function casts(): array
    {
        return [
            'method' => HttpMethod::class,
            'headers' => 'json',
            'payload_json' => 'json',
            'render' => RenderEngine::class,
            'secret' => 'encrypted',
        ];
    }
}
