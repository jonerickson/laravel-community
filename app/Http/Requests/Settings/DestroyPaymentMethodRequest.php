<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Override;

class DestroyPaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'method' => ['required', 'string'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'method.required' => 'Payment method is required.',
            'method.string' => 'Payment method must be a valid string.',
        ];
    }
}
