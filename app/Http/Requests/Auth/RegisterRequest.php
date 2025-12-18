<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Rules\BlacklistRule;
use App\Rules\NoProfanity;
use App\Settings\RegistrationSettings;
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
        $rules = [
            'name' => ['required', 'string', 'min:2', 'max:32', new NoProfanity, new BlacklistRule],
            'email' => ['required', 'string', 'email', 'lowercase', 'max:255', 'unique:'.User::class, new NoProfanity, new BlacklistRule],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];

        $policies = app(RegistrationSettings::class)->required_policy_ids;

        foreach ($policies as $policy) {
            $rules['policy.'.$policy] = ['required'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'username',
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function messages(): array
    {
        return [
            'name.required' => 'Please enter your username.',
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already registered.',
            'password.required' => 'Please create a password.',
            'password.confirmed' => 'The password confirmation does not match.',
            'policy.*.required' => 'You must agree to each policy to continue.',
        ];
    }
}
