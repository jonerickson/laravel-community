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
        return Auth::check() && Auth::user()->can('update', $this->route('ticket'));
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:close,resolve,open'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'action.required' => 'Action is required.',
            'action.string' => 'Action must be a valid string.',
            'action.in' => 'Action must be one of: close, resolve, open.',
        ];
    }
}
