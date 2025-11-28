<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Frontend\Profile;

use App\Actions\Users\SyncProfileAndIntegrationsAction;
use App\Http\Resources\ApiResource;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Throwable;

class SyncController
{
    public function __construct(
        #[CurrentUser]
        protected readonly User $user,
    ) {
        //
    }

    public function __invoke(): ApiResource
    {
        try {
            SyncProfileAndIntegrationsAction::execute($this->user);
        } catch (Throwable) {
            return ApiResource::error(
                message: 'We were unable to sync your profile and accounts. Please try again later.',
            );
        }

        return ApiResource::success(
            message: 'Your profile has been successfully synced.',
        );
    }
}
