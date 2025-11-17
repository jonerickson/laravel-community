<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Frontend;

use Illuminate\Foundation\Http\FormRequest;

class StoreFingerprintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'fingerprint_id' => ['required', 'string', 'max:255'],
            'request_id' => ['required', 'string', 'max:255'],
        ];
    }
}
