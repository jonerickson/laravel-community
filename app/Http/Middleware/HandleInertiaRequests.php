<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Data\AuthData;
use App\Data\FlashData;
use App\Data\SharedData;
use App\Data\UserData;
use App\Models\Permission;
use App\Services\ShoppingCartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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
        $sharedData = SharedData::from([
            'auth' => AuthData::from([
                'user' => ($user = $request->user()) ? UserData::from($user) : null,
                'isAdmin' => $user?->hasRole('super-admin') ?? false,
                'roles' => $user?->roles?->pluck('name')->toArray() ?? [],
                'can' => Permission::all()->mapWithKeys(fn (Permission $permission) => [$permission->name => Gate::forUser($user)->check($permission->name)])->toArray(),
                'mustVerifyEmail' => $user && ! $user->hasVerifiedEmail(),
            ]),
            'cartCount' => $this->shoppingCartService->getCartCount(),
            'flash' => null,
            'name' => config('app.name'),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'ziggy' => [],
        ]);

        return [
            ...parent::share($request),
            ...$sharedData->toArray(),
            'flash' => fn () => FlashData::from([
                'scrollToBottom' => $request->session()->pull('scrollToBottom'),
                'message' => $request->session()->pull('message'),
                'messageVariant' => $request->session()->pull('messageVariant'),
            ]),
            'ziggy' => fn (): array => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }
}
