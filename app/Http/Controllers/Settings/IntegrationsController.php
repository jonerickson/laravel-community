<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Data\UserIntegrationData;
use App\Http\Controllers\Controller;
use App\Models\UserIntegration;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class IntegrationsController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();

        $connectedAccounts = $user
            ->integrations()
            ->select(['id', 'user_id', 'provider', 'provider_id', 'provider_name', 'provider_email', 'provider_avatar', 'created_at', 'updated_at'])
            ->get();

        return Inertia::render('settings/integrations', [
            'connectedAccounts' => UserIntegrationData::collect($connectedAccounts),
        ]);
    }

    public function destroy(UserIntegration $social): RedirectResponse
    {
        abort_unless($social->user_id === Auth::id(), 403);

        $social->delete();

        return back()->with('message', 'The integration was successfully deleted.');
    }
}
