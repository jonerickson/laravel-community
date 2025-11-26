<?php

declare(strict_types=1);

namespace App\Listeners\Webhooks;

use App\Models\Log;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Spatie\WebhookServer\Events\FinalWebhookCallFailedEvent;
use Spatie\WebhookServer\Events\WebhookCallSucceededEvent;

class UpdateWebhookStatus implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;

    public function handle(WebhookCallSucceededEvent|FinalWebhookCallFailedEvent $event): void
    {
        $log = Log::find(data_get($event->meta, 'log_id'));

        if (filled($log) && $event->response instanceof Response) {
            $log->update([
                'status' => $event->response->getStatusCode(),
                'response_content' => $event->response->getBody(),
                'response_headers' => collect($event->response->getHeaders())->map(fn ($value): ?string => $value[0] ?? null)->toArray(),
            ]);
        }
    }
}
