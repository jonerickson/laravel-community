<?php

declare(strict_types=1);

namespace App\Http\Requests\Forums;

use App\Models\Post;
use App\Rules\NoProfanity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Override;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()->can('create', Post::class);
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
            'content.required' => 'Please provide a reply before posting.',
        ];
    }
}
