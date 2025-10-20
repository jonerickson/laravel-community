<?php

declare(strict_types=1);

namespace App\Http\Requests\Onboarding;

use App\Rules\NoProfanity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Override;

class OnboardingProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'bio' => ['nullable', 'string', 'max:500', new NoProfanity],
            'role' => ['required', 'string', 'in:developer,creator,player,other'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'role.required' => 'Please select what brings you here',
            'role.in' => 'Please select a valid option',
            'bio.max' => 'Bio must not exceed 500 characters',
        ];
    }
}
