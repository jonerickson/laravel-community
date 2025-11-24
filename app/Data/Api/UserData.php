<?php

declare(strict_types=1);

namespace App\Data\Api;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Serializer\AbstractItemNormalizer;
use App\Api\State\Providers\MeProvider;
use App\Api\State\Providers\UserProvider;
use App\Facades\PaymentProcessor;
use App\Models\User;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Data;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(uriTemplate: 'me', provider: MeProvider::class),
        new Get(uriTemplate: 'users/{id}', provider: UserProvider::class),
        new GetCollection(uriTemplate: 'users', provider: UserProvider::class),
    ],
    normalizationContext: [
        AbstractItemNormalizer::GROUPS => ['user'],
    ],
)]
class UserData extends Data
{
    #[ApiProperty(identifier: true, property: 'id', serialize: new Groups(['user']))]
    public int $id;

    #[ApiProperty(property: 'referenceId', serialize: new Groups(['user']))]
    public string $reference_id;

    #[ApiProperty(property: 'name', serialize: new Groups(['user']))]
    public string $name;

    #[ApiProperty(property: 'email', serialize: new Groups(['user']))]
    public string $email;

    #[ApiProperty(property: 'subscription', serialize: new Groups(['user']))]
    #[Computed]
    public ?UserSubscription $subscription = null;

    /**
     * @var UserIntegrationData[]
     */
    #[ApiProperty(property: 'integrations', serialize: new Groups(['user']))]
    public array $integrations = [];

    /**
     * @var UserProductData[]
     */
    #[ApiProperty(property: 'products', serialize: new Groups(['user']))]
    public array $products = [];

    public static function from(mixed ...$payloads): static
    {
        $object = parent::from(...$payloads);

        if (($user = User::find($object->id)) && ($subscription = PaymentProcessor::currentSubscription($user))) {
            $object->subscription = UserSubscription::from([
                'id' => $user->getKey(),
                'name' => $subscription->product?->name,
                'status' => $subscription->status,
                'product_reference_id' => $subscription->product?->referenceId,
                'created_at' => $subscription->createdAt,
                'updated_at' => $subscription->updatedAt,
                'ends_at' => $subscription->endsAt,
                'trial_ends_at' => $subscription->trialEndsAt,
            ]);
        }

        return $object;
    }
}
