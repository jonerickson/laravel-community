<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Data\AuthData;
use App\Data\FlashData;
use App\Data\UserData;
use App\Services\PermissionService;
use App\Services\ShoppingCartService;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function __construct(private readonly ShoppingCartService $shoppingCartService)
    {
        //
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => AuthData::from([
                'user' => ($user = $request->user()) ? UserData::from($user) : null,
                'isAdmin' => $user?->hasRole('super-admin') ?? false,
                'roles' => $user?->roles?->pluck('name')->toArray() ?? [],
                'can' => PermissionService::mapFrontendPermissions($user),
                'mustVerifyEmail' => $user && ! $user->hasVerifiedEmail(),
            ]),
            'cartCount' => $this->shoppingCartService->getCartCount(),
            'flash' => fn () => FlashData::from([
                'scrollToBottom' => $request->session()->pull('scrollToBottom'),
                'message' => $request->session()->pull('message'),
                'messageVariant' => $request->session()->pull('messageVariant'),
            ]),
            'name' => config('app.name'),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'ziggy' => fn (): array => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }
}
