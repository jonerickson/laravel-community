<?php

declare(strict_types=1);

namespace App\Listeners\Webhooks;

use App\Enums\RenderEngine;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderRefunded;
use App\Events\PaymentSucceeded;
use App\Events\SubscriptionCreated;
use App\Events\SubscriptionDeleted;
use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Events\UserUpdated;
use App\Facades\ExpressionLanguage;
use App\Models\Webhook;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Spatie\WebhookServer\WebhookCall;

class SendWebhooks implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;

    public function handle(OrderCreated|OrderCancelled|OrderRefunded|PaymentSucceeded|SubscriptionCreated|SubscriptionDeleted|UserCreated|UserUpdated|UserDeleted $event): void
    {
        Webhook::query()->whereEvent($event::class)->each(function (Webhook $webhook) use ($event): void {
            if (($webhook->render === RenderEngine::Blade && blank($webhook->payload_text)) || ($webhook->render === RenderEngine::ExpressionLanguage && blank($webhook->payload_json))) {
                return;
            }

            if ($webhook->render === RenderEngine::ExpressionLanguage) {
                $payload = ExpressionLanguage::evaluate($webhook->payload_json, [
                    'event' => $event,
                ]);
            } else {
                $json = Blade::render($webhook->payload_text, ['event' => $event]);

                if (! Str::isJson($json)) {
                    return;
                }

                $payload = json_decode($json, true);
            }

            if (! is_array($payload) || $payload === []) {
                return;
            }

            $log = $webhook->logs()->create([
                'endpoint' => $webhook->url,
                'method' => $webhook->method->value,
                'request_body' => $payload,
                'request_headers' => $webhook->headers,
            ]);

            WebhookCall::create()
                ->url($webhook->url)
                ->withHeaders($webhook->headers)
                ->meta(['log_id' => $log->getKey()])
                ->useHttpVerb($webhook->method->value)
                ->useSecret($webhook->secret)
                ->payload($payload)
                ->onQueue('webhooks')
                ->dispatch();
        });
    }
}
