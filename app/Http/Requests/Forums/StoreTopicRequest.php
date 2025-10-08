<?php

declare(strict_types=1);

namespace App\Http\Requests\Forums;

use App\Models\Topic;
use App\Rules\NoProfanity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Override;

class StoreTopicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()->can('create', Topic::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255', new NoProfanity],
            'description' => ['nullable', 'string', 'max:500', new NoProfanity],
            'content' => ['required', 'string', new NoProfanity],
        ];
    }

    #[Override]
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
