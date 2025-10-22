<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Rules\BlacklistRule;
use App\Rules\NoProfanity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Override;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', new NoProfanity, new BlacklistRule],
            'email' => ['required', 'string', 'lowercase', 'max:255', 'unique:'.User::class, new NoProfanity, new BlacklistRule],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'name.required' => 'Please enter your name',
            'email.required' => 'Please enter your email address',
            'email.email' => 'Please enter a valid email address',
            'email.unique' => 'This email is already registered',
            'password.required' => 'Please create a password',
            'password.confirmed' => 'Password confirmation does not match',
        ];
    }
}
