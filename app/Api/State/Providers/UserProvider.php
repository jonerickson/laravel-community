<?php

declare(strict_types=1);

namespace App\Api\State\Providers;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Data\Api\UserData;
use App\Enums\OrderStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $query = User::query()
            ->with('integrations')
            ->with(['orders' => function (HasMany $query): void {
                $query
                    ->with('items.price.product')
                    ->where('status', OrderStatus::Succeeded);
            }]);

        return value(match ($operation::class) {
            GetCollection::class => UserData::collect($query->get()),
            Get::class => function (Builder $query, array $uriVariables): ?UserData {
                if (! $user = $query->whereKey(data_get($uriVariables, 'id'))->first()) {
                    return null;
                }

                return UserData::from($user);
            },
        }, $query, $uriVariables);
    }
}
