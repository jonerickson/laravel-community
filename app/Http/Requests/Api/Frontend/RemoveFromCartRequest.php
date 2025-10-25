<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Frontend;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class RemoveFromCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'price_id' => ['nullable', 'exists:prices,id'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'price_id.exists' => 'The selected price is invalid.',
        ];
    }
}
