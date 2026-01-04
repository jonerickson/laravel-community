<?php

declare(strict_types=1);

namespace App\Http\Requests\SupportTickets;

use App\Rules\BlacklistRule;
use App\Rules\NoProfanity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Override;

class StoreSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'min:2', 'max:255', new NoProfanity, new BlacklistRule],
            'description' => ['required', 'string', 'min:2', 'max:10000', new NoProfanity, new BlacklistRule],
            'support_ticket_category_id' => ['required', 'exists:support_tickets_categories,id'],
            'order_id' => ['nullable', 'exists:orders,id,user_id,'.Auth::id()],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function messages(): array
    {
        return [
            'subject.required' => 'Please provide a subject for your support ticket.',
            'subject.max' => 'The subject cannot be longer than 255 characters.',
            'description.required' => 'Please describe your issue or question.',
            'description.max' => 'The description cannot be longer than 10,000 characters.',
            'support_ticket_category_id.required' => 'Please select a category for your support ticket.',
            'support_ticket_category_id.exists' => 'The selected category is invalid.',
            'order_id.exists' => 'The selected order is invalid or does not belong to you.',
        ];
    }
}
