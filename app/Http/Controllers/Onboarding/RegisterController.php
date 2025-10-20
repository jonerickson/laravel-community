<?php

declare(strict_types=1);

namespace App\Http\Controllers\Onboarding;

use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\OnboardingService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class RegisterController
{
    public function __construct(
        private readonly OnboardingService $onboardingService,
    ) {}

    public function __invoke(RegisterRequest $request): Response
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
        ]);

        event(new Registered($user));

        Auth::login($user);

        $this->onboardingService->advanceToStep(1);

        return inertia()->location(route('onboarding'));
    }
}
