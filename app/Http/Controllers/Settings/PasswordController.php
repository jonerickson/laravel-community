<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdatePasswordRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PasswordController extends Controller
{
    public function __construct(
        #[CurrentUser]
        private readonly User $user,
    ) {
        //
    }

    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        $this->user->update([
            'password' => Hash::make($request->validated('password')),
        ]);

        return back()->with('status', 'Password updated successfully.');
    }
}
