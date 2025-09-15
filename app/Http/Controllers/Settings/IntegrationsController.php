<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\UserSocial;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class IntegrationsController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();

        $connectedAccounts = $user->socials()
            ->select(['id', 'provider', 'provider_name', 'provider_email', 'provider_avatar', 'created_at'])
            ->get();

        return Inertia::render('settings/integrations', [
            'connectedAccounts' => $connectedAccounts,
        ]);
    }

    public function destroy(UserSocial $social): RedirectResponse
    {
        abort_unless($social->user_id === Auth::id(), 403);

        $social->delete();

        return to_route('settings.integrations.index');
    }
}
