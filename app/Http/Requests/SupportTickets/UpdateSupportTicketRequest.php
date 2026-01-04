<?php

declare(strict_types=1);

namespace App\Http\Requests\SupportTickets;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Override;

class UpdateSupportTicketRequest extends FormRequest
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
            'action' => ['required', 'string', 'in:close,resolve,open'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function messages(): array
    {
        return [
            'action.required' => 'An action is required.',
            'action.string' => 'The action must be a valid string.',
            'action.in' => 'The action must be one of: close, resolve, open.',
        ];
    }
}
