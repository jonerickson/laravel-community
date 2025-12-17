<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Data\DiscountData;
use App\Enums\DiscountType;
use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DiscountController extends Controller
{
    public function __construct(
        #[CurrentUser]
        private readonly User $user,
    ) {
        //
    }

    public function __invoke(): Response
    {
        return Inertia::render('settings/discounts', [
            'discounts' => Inertia::defer(fn (): Collection => DiscountData::collect(Discount::query()
                ->whereBelongsTo($this->user, 'customer')
                ->where('type', '<>', DiscountType::Cancellation)
                ->latest()
                ->get())),
        ]);
    }
}
