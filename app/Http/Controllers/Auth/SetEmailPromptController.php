<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SetEmailRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SetEmailPromptController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        return filled($request->user()->email)
            ? redirect()->intended(route('dashboard', absolute: false))
            : Inertia::render('auth/set-email', ['status' => $request->session()->get('status')]);
    }

    public function store(SetEmailRequest $request): RedirectResponse
    {
        $request->user()->update([
            'email' => $request->validated('email'),
        ]);

        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->sendEmailVerificationNotification();
        }

        return back()->with('status', 'A verification link has been sent to the email address you provided. Please check your email and click the link to continue setting your account email.');
    }
}
