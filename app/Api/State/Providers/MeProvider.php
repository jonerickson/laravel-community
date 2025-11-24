<?php

declare(strict_types=1);

namespace App\Api\State\Providers;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Data\Api\UserData;
use Illuminate\Support\Facades\Auth;

class MeProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        return UserData::from(Auth::user());
    }
}
