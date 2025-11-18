<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Data\AnnouncementData;
use App\Data\AuthData;
use App\Data\FlashData;
use App\Data\NavigationPageData;
use App\Data\SharedData;
use App\Data\UserData;
use App\Enums\Role;
use App\Models\Announcement;
use App\Models\Page;
use App\Models\Permission;
use App\Models\Post;
use App\Models\User;
use App\Services\ShoppingCartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Number;
use Inertia\Middleware;
use Override;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function __construct(private readonly ShoppingCartService $shoppingCartService)
    {
        //
    }

    #[Override]
    public function share(Request $request): array
    {
        $user = $request->user();

        if ($user) {
            $user->loadMissing([
                'activeWarningsWithActiveConsequence.warningConsequence',
                'userWarnings',
                'activeWarnings.warning',
            ]);
        }

        $sharedData = SharedData::from([
            'auth' => AuthData::from([
                'user' => $user ? UserData::from($user) : null,
                'isAdmin' => $user?->hasAnyRole(Role::Administrator, Role::SupportAgent) ?? false,
                'roles' => $user?->roles?->pluck('name')->toArray() ?? [],
                'can' => Permission::all()->mapWithKeys(fn (Permission $permission): array => [$permission->name => Gate::forUser($user)->check($permission->name)])->toArray(),
                'mustVerifyEmail' => $user && ! $user->hasVerifiedEmail(),
            ]),
            'announcements' => AnnouncementData::collect(Announcement::query()
                ->with(['author', 'reads'])
                ->current()
                ->unread()
                ->latest()
                ->get()),
            'navigationPages' => Cache::remember('navigation_pages', now()->addHour(), fn () => Page::query()
                ->published()
                ->inNavigation()
                ->get()
                ->map(fn (Page $page): NavigationPageData => NavigationPageData::from([
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'label' => $page->navigation_label ?? $page->title,
                    'order' => $page->navigation_order,
                    'url' => $page->url,
                ]))
                ->toArray()),
            'cartCount' => $this->shoppingCartService->getCartCount(),
            'memberCount' => Cache::remember('member_count', now()->addHour(), fn () => Number::abbreviate(User::count())),
            'postCount' => Cache::remember('post_count', now()->addHour(), fn () => Number::abbreviate(Post::count())),
            'flash' => null,
            'name' => config('app.name'),
            'email' => config('app.email'),
            'phone' => config('app.phone'),
            'address' => config('app.address'),
            'slogan' => config('app.slogan'),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'ziggy' => [],
        ]);

        return [
            ...parent::share($request),
            ...$sharedData->toArray(),
            'flash' => fn (): FlashData => FlashData::from([
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
