<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Models\Field;
use App\Rules\BlacklistRule;
use App\Rules\NoProfanity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Override;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        $fields = Field::query()->get()->mapWithKeys(fn (Field $field): array => ['fields.'.$field->id => $field->type->getRules($field)])->toArray();

        return array_merge($fields, [
            'name' => ['required', 'string', 'min:2', 'max:32', new NoProfanity, new BlacklistRule],
            'signature' => ['nullable', 'string', 'max:500', new NoProfanity, new BlacklistRule],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);
    }

    #[Override]
    public function attributes(): array
    {
        $fields = Field::query()->get()->mapWithKeys(fn (Field $field): array => ['fields.'.$field->id => Str::lower($field->label)])->toArray();

        return array_merge($fields, [
            'name' => 'username',
            'signature' => 'signature',
            'avatar' => 'avatar',
        ]);
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function messages(): array
    {
        return [
            'name.required' => 'Please provide your username.',
            'signature.max' => 'Your signature cannot be longer than 500 characters.',
            'avatar.image' => 'The avatar must be an image file.',
            'avatar.max' => 'The avatar file size cannot exceed 2MB.',
        ];
    }
}
