<?php

declare(strict_types=1);

namespace App\Http\Requests\Onboarding;

use App\Models\Field;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class OnboardingProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return Field::query()->get()->mapWithKeys(fn(Field $field): array => [$field->name => $field->type->getRules($field)])->toArray();
    }
}
