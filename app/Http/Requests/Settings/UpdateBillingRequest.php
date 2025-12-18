<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Override;

class UpdateBillingRequest extends FormRequest
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
            'billing_address' => ['required', 'string', 'max:255'],
            'billing_address_line_2' => ['nullable', 'string', 'max:255'],
            'billing_city' => ['required', 'string', 'max:255'],
            'billing_state' => ['required', 'string', 'max:255'],
            'billing_postal_code' => ['required', 'string', 'max:25'],
            'billing_country' => ['required', 'string', 'size:2'],
            'vat_id' => ['nullable', 'string', 'max:50'],
            'extra_billing_information' => ['nullable', 'string', 'max:65535'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function messages(): array
    {
        return [
            'billing_country.size' => 'The billing country must be a valid 2-character country code.',
            'billing_postal_code.max' => 'The postal code may not be greater than 20 characters.',
            'vat_id.max' => 'The VAT ID may not be greater than 50 characters.',
            'extra_billing_information.max' => 'The additional information may not be greater than 1,000 characters.',
        ];
    }
}
