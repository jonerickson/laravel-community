<?php

declare(strict_types=1);

namespace App\Http\Requests\SupportTickets;

use App\Rules\NoProfanity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Override;
use PHPUnit\Framework\Attributes\Ticket;

class StoreSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->can('create', Ticket::class);
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255', new NoProfanity],
            'description' => ['required', 'string', 'max:10000', new NoProfanity],
            'support_ticket_category_id' => ['required', 'exists:support_tickets_categories,id'],
            'order_id' => ['nullable', 'exists:orders,id,user_id,'.Auth::id()],
        ];
    }

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
