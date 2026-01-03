<?php

declare(strict_types=1);

namespace App\Http\Requests\SupportTickets;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreSupportTicketAttachmentRequest extends FormRequest
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
            'attachment' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,txt,png,jpg,jpeg,gif,heif'],
        ];
    }
}
