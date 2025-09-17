<?php

declare(strict_types=1);

namespace App\Http\Requests\Forums;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreTopicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Please provide a title for your topic.',
            'title.max' => 'The title cannot be longer than 255 characters.',
            'description.max' => 'The description cannot be longer than 500 characters.',
            'content.required' => 'Please provide content for your topic.',
        ];
    }
}
