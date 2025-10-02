<?php

declare(strict_types=1);

namespace App\Api\State\Providers;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Data\Api\UserData;
use App\Models\User;

class UserProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $query = User::query()
            ->with('integrations')
            ->with('orders.items.product');

        return match ($operation::class) {
            GetCollection::class => UserData::collect($query->get()),
            Get::class => UserData::from($query->whereKey(data_get($uriVariables, 'id'))->first()),
        };
    }
}
