<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user(),
                'isAdmin' => $request->user()?->hasRole(Utils::getSuperAdminName()),
            ],
            'ziggy' => fn (): array => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'cartCount' => $this->getCartCount(),
        ];
    }

    private function getCartCount(): int
    {
        $cart = Session::get('shopping_cart', []);

        return collect($cart)->sum('quantity');
    }
}
