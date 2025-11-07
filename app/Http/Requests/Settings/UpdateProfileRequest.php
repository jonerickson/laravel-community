<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Models\Field;
use App\Rules\BlacklistRule;
use App\Rules\NoProfanity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Override;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        $fields = Field::query()->get()->mapWithKeys(fn (Field $field): array => [$field->name => $field->type->getRules($field)])->toArray();

        return array_merge($fields, [
            'name' => ['required', 'string', 'max:255'],
            'signature' => ['nullable', 'string', 'max:500', new NoProfanity, new BlacklistRule],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);
    }

    #[Override]
    public function messages(): array
    {
        return [
            'name.required' => 'Please provide your name.',
            'name.max' => 'Your name cannot be longer than 255 characters.',
            'signature.max' => 'Your signature cannot be longer than 500 characters.',
            'avatar.image' => 'The avatar must be an image file.',
            'avatar.max' => 'The avatar file size cannot exceed 2MB.',
        ];
    }
}
