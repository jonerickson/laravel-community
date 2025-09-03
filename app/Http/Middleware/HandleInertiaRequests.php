<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user = $request->user(),
                'groups' => $user?->groups?->pluck(['id', 'name', 'color']),
                'isAdmin' => $user?->hasRole('super-admin'),
                'roles' => $user?->roles?->pluck('name'),
                'can' => $user?->getPermissionsViaRoles()->mapWithKeys(fn (Permission $permission) => [$permission->name => $user->can($permission->name)])->toArray(),
            ],
            'cartCount' => $this->getCartCount(),
            'flash' => [
                'scrollToBottom' => fn () => $request->session()->pull('scrollToBottom'),
                'message' => fn () => $request->session()->pull('message'),
                'messageVariant' => fn () => $request->session()->pull('messageVariant'),
            ],
            'name' => config('app.name'),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'ziggy' => fn (): array => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }

    private function getCartCount(): int
    {
        $cart = Session::get('shopping_cart', []);

        return collect($cart)->sum('quantity');
    }
}
