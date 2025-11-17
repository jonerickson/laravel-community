<?php

declare(strict_types=1);

namespace App\Http\Requests\Onboarding;

use App\Models\Field;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Override;

class OnboardingProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return Field::query()->get()->mapWithKeys(fn (Field $field): array => ['fields.'.$field->id => $field->type->getRules($field)])->toArray();
    }

    #[Override]
    public function attributes(): array
    {
        return Field::query()->get()->mapWithKeys(fn (Field $field): array => ['fields.'.$field->id => Str::lower($field->label)])->toArray();
    }
}
