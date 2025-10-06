<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\SendMagicLinkAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendMagicLinkRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class MagicLinkController extends Controller
{
    public function index(Request $request, User $user): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(401, 'This magic link is invalid or has expired.');
        }

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function create(Request $request): Response
    {
        return Inertia::render('auth/magic-link', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(SendMagicLinkRequest $request): RedirectResponse
    {
        $user = User::where('email', $request->email)->first();

        if ($user) {
            SendMagicLinkAction::execute($user);
        }

        return back()->with('status', 'If an account exists with that email address, a magic link has been sent.');
    }
}
