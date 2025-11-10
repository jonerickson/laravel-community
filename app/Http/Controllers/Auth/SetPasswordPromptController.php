<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SetPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class SetPasswordPromptController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        return filled($request->user()->password)
            ? redirect()->intended(route('dashboard', absolute: false))
            : Inertia::render('auth/set-password', ['status' => $request->session()->get('status')]);
    }

    public function store(SetPasswordRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => Hash::make($request->validated('password')),
        ]);

        return back()->with('status', 'Your password has been successfully set.');
    }
}
