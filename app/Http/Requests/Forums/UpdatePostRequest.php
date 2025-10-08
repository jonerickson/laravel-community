<?php

declare(strict_types=1);

namespace App\Http\Requests\Forums;

use App\Rules\NoProfanity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Override;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()->can('update', $this->route('post'));
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', new NoProfanity],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'content.required' => 'Post content cannot be empty.',
        ];
    }
}
