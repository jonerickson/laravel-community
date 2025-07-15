<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    public function __invoke()
    {
        return Inertia::render('store/subscriptions');
    }
}
