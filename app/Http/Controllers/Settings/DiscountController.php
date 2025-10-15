<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Data\DiscountData;
use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
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
            'discounts' => Inertia::defer(fn (): \Spatie\LaravelData\DataCollection|\Spatie\LaravelData\PaginatedDataCollection|\Spatie\LaravelData\CursorPaginatedDataCollection|\Illuminate\Support\Enumerable|\Illuminate\Pagination\AbstractPaginator|\Illuminate\Contracts\Pagination\Paginator|\Illuminate\Pagination\AbstractCursorPaginator|\Illuminate\Contracts\Pagination\CursorPaginator|array => DiscountData::collect(Discount::query()
                ->whereBelongsTo($this->user, 'customer')
                ->latest()
                ->get())),
        ]);
    }
}
