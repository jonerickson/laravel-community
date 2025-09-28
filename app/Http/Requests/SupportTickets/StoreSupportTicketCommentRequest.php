<?php

declare(strict_types=1);

namespace App\Http\Requests\SupportTickets;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Override;

class StoreSupportTicketCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && $this->route('ticket')->isAuthoredBy(Auth::user());
    }

    public function rules(): array
    {
        return [
            'content' => 'required|string|max:10000',
            'parent_id' => 'nullable|int|exists:comments,id',
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'content.required' => 'Please enter a comment.',
            'content.max' => 'The comment cannot be longer than 10,000 characters.',
            'parent_id.exists' => 'The selected parent comment does not exist.',
        ];
    }
}
