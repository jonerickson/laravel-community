<?php

declare(strict_types=1);

namespace App\Http\Requests\SupportTickets;

use App\Rules\BlacklistRule;
use App\Rules\NoProfanity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Override;

class StoreSupportTicketCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:2', 'max:10000', new NoProfanity, new BlacklistRule],
            'parent_id' => ['nullable', 'int', 'exists:comments,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
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
