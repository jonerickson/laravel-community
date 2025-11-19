<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WebhookMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $event
 * @property string $url
 * @property WebhookMethod $method
 * @property array<array-key, mixed>|null $headers
 * @property string|null $resource_type
 * @property int|null $resource_id
 * @property array<array-key, mixed>|null $payload
 * @property string $secret
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook whereResourceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook whereResourceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook whereSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook whereUrl($value)
 *
 * @mixin \Eloquent
 */
class Webhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'event',
        'url',
        'method',
        'headers',
        'payload',
        'secret',
    ];

    protected function casts(): array
    {
        return [
            'method' => WebhookMethod::class,
            'headers' => 'json',
            'payload' => 'json',
            'secret' => 'encrypted',
        ];
    }
}
