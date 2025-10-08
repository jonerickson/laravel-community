<?php

declare(strict_types=1);

namespace App\Http\Requests\SupportTickets;

use App\Models\Comment;
use App\Rules\NoProfanity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Override;

class StoreSupportTicketCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()->can('create', Comment::class);
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:10000', new NoProfanity],
            'parent_id' => ['nullable', 'int', 'exists:comments,id'],
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
