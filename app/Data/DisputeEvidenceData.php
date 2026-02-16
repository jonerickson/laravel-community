<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Log;
use App\Models\Order;
use App\Models\UserIntegration;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;
use Spatie\LaravelData\Data;

class DisputeEvidenceData extends Data
{
    /**
     * @param  array<int, array{name: string, amount: float|int}>  $orderItems
     * @param  array<int, array{provider: string, provider_id: string, provider_name: string|null}>  $integrations
     * @param  array<int, array{title: string, version: string|null, consented_at: Carbon|null, ip_address: string|null, fingerprint_id: string|null, user_agent: string|null}>  $consents
     * @param  array<int, array{description: string, event: string, properties: array<string, mixed>, created_at: Carbon|null}>  $activityLogs
     * @param  array<int, array{endpoint: string, method: string, status: int|null, created_at: Carbon|null}>  $accessLogs
     */
    public function __construct(
        public string $referenceId,
        public ?string $externalOrderId,
        public ?string $externalInvoiceId,
        public ?string $externalEventId,
        public float|int $amountPaid,
        public string $status,
        public Carbon $orderCreatedAt,
        public array $orderItems,
        public string $userEmail,
        public Carbon $userCreatedAt,
        public array $integrations,
        public array $consents,
        public array $activityLogs,
        public array $accessLogs,
    ) {}

    public static function fromOrder(Order $order): self
    {
        $order->loadMissing(['items.price.product', 'user.integrations', 'user.policyConsents.policy']);

        $user = $order->user;

        $integrations = $user->integrations->map(fn (UserIntegration $integration): array => [
            'provider' => $integration->provider,
            'provider_id' => $integration->provider_id,
            'provider_name' => $integration->provider_name,
        ])->all();

        $consents = $user->policyConsents->map(fn ($consent): array => [
            'title' => $consent->policy->title,
            'version' => $consent->version ?? $consent->policy->version,
            'consented_at' => $consent->consented_at,
            'ip_address' => $consent->ip_address,
            'fingerprint_id' => $consent->fingerprint_id,
            'user_agent' => $consent->user_agent,
        ])->all();

        $activityLogs = Activity::query()
            ->where('subject_type', UserIntegration::class)
            ->where('causer_id', $user->id)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Activity $activity): array => [
                'description' => $activity->description,
                'event' => $activity->event ?? '',
                'properties' => $activity->properties->toArray(),
                'created_at' => $activity->created_at,
            ])->all();

        $orderItems = $order->items->map(fn ($item): array => [
            'name' => $item->name ?? $item->price?->product?->name ?? 'Unknown Product',
            'amount' => $item->amount,
        ])->all();

        $endpointIdentifiers = $user->integrations
            ->pluck('provider_id')
            ->prepend($user->id)
            ->filter()
            ->map(fn (string|int $id): string => '/'.$id)
            ->all();

        $accessLogs = Log::query()
            ->where(function ($query) use ($endpointIdentifiers): void {
                foreach ($endpointIdentifiers as $suffix) {
                    $query->orWhere('endpoint', 'like', '%'.$suffix);
                }
            })
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (Log $log): array => [
                'endpoint' => $log->endpoint,
                'method' => $log->method->value,
                'status' => $log->status?->value,
                'created_at' => $log->created_at,
            ])->all();

        return new self(
            referenceId: $order->reference_id,
            externalOrderId: $order->external_order_id,
            externalInvoiceId: $order->external_invoice_id,
            externalEventId: $order->external_event_id,
            amountPaid: $order->amount,
            status: $order->status->getLabel(),
            orderCreatedAt: $order->created_at,
            orderItems: $orderItems,
            userEmail: $user->email ?? '',
            userCreatedAt: $user->created_at,
            integrations: $integrations,
            consents: $consents,
            activityLogs: $activityLogs,
            accessLogs: $accessLogs,
        );
    }
}
