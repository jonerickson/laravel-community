<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SubscriptionCreated;
use App\Events\SubscriptionDeleted;
use App\Facades\ExpressionLanguage;
use App\Models\Webhook;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Spatie\WebhookServer\WebhookCall;

class SendWebhook implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;

    public function handle(SubscriptionCreated|SubscriptionDeleted $event): void
    {
        Webhook::query()->whereEvent($event::class)->each(function (Webhook $webhook) use ($event): void {
            if (blank($webhook->payload)) {
                return;
            }

            $payload = ExpressionLanguage::evaluate($webhook->payload, [
                'event' => $event,
            ]);

            if (is_null($payload) || $payload === []) {
                return;
            }

            $webhook->logs()->create([
                'endpoint' => $webhook->url,
                'method' => $webhook->method->value,
                'request_body' => $payload,
                'request_headers' => $webhook->headers,
            ]);

            WebhookCall::create()
                ->url($webhook->url)
                ->withHeaders($webhook->headers)
                ->useHttpVerb($webhook->method->value)
                ->useSecret($webhook->secret)
                ->payload($payload)
                ->dispatch();
        });
    }
}
