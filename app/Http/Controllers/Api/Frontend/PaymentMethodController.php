<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Frontend;

use App\Data\PaymentSetupIntentData;
use App\Http\Resources\ApiResource;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;

class PaymentMethodController
{
    public function __construct(
        #[CurrentUser]
        private readonly User $user,
    ) {
        //
    }

    public function __invoke(): ApiResource
    {
        $setupIntent = $this->user->createSetupIntent();

        $setupIntentData = PaymentSetupIntentData::from([
            'id' => $setupIntent->id,
            'clientSecret' => $setupIntent->client_secret,
            'status' => $setupIntent->status,
            'customer' => $setupIntent->customer,
            'paymentMethodTypes' => $setupIntent->payment_method_types,
            'usage' => $setupIntent->usage,
        ]);

        return ApiResource::success(
            resource: $setupIntentData
        );
    }
}
