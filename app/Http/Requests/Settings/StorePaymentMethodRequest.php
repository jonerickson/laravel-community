<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Override;

class StorePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'method' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function messages(): array
    {
        return [
            'method.required' => 'A payment method is required.',
            'method.string' => 'The payment method must be a valid string.',
        ];
    }
}
