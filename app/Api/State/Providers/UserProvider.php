<?php

declare(strict_types=1);

namespace App\Api\State\Providers;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Data\Api\UserData;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Spatie\LaravelData\PaginatedDataCollection;

class UserProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $query = User::query()->with(['integrations', 'products']);

        $request = $context['request'] ?? request();

        return value(match ($operation::class) {
            GetCollection::class => UserData::collect($query->paginate(
                perPage: $request->query(config('api-platform.pagination.items_per_page_parameter_name'), config('api-platform.defaults.pagination_items_per_page')),
                pageName: config('api-platform.pagination.page_parameter_name'),
            ), PaginatedDataCollection::class),
            Get::class => function (Builder $query, array $uriVariables): ?UserData {
                $id = data_get($uriVariables, 'id');

                if (! $user = $query->whereKey($id)->orWhereRelation('integrations', 'provider_id', $id)->first()) {
                    return null;
                }

                return UserData::from($user);
            },
        }, $query, $uriVariables);
    }
}
