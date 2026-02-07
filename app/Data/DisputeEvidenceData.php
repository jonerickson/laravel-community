<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Order;
use App\Models\UserIntegration;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;
use Spatie\LaravelData\Data;

class DisputeEvidenceData extends Data
{
    /**
     * @param  array<int, array{name: string, amount: float|int}>  $orderItems
     * @param  array<int, array{title: string, version: string|null, consented_at: Carbon|null, ip_address: string|null}>  $consents
     * @param  array<int, array{description: string, event: string, properties: array<string, mixed>, created_at: Carbon|null}>  $activityLogs
     */
    public function __construct(
        public string $referenceId,
        public ?string $externalPaymentId,
        public float|int $amountPaid,
        public string $status,
        public Carbon $orderCreatedAt,
        public array $orderItems,
        public string $userEmail,
        public ?string $robloxId,
        public ?string $robloxName,
        public Carbon $userCreatedAt,
        public array $consents,
        public array $activityLogs,
    ) {}

    public static function fromOrder(Order $order): self
    {
        $order->loadMissing(['items.price.product', 'user.integrations', 'user.policyConsents.policy']);

        $user = $order->user;

        $robloxIntegration = $user->integrations
            ->first(fn (UserIntegration $integration): bool => $integration->provider === 'roblox');

        $consents = $user->policyConsents->map(fn ($consent): array => [
            'title' => $consent->policy->title,
            'version' => $consent->policy->version,
            'consented_at' => $consent->consented_at,
            'ip_address' => $consent->ip_address,
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

        return new self(
            referenceId: $order->reference_id,
            externalPaymentId: $order->external_payment_id,
            amountPaid: $order->amount,
            status: $order->status->getLabel(),
            orderCreatedAt: $order->created_at,
            orderItems: $orderItems,
            userEmail: $user->email ?? '',
            robloxId: $robloxIntegration?->provider_id,
            robloxName: $robloxIntegration?->provider_name,
            userCreatedAt: $user->created_at,
            consents: $consents,
            activityLogs: $activityLogs,
        );
    }
}
