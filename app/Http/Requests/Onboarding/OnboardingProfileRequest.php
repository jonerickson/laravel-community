<?php

declare(strict_types=1);

namespace App\Http\Requests\Onboarding;

use App\Models\Field;
use App\Rules\NoProfanity;
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
        return Field::query()->get()->mapWithKeys(function (Field $field): array {
            $rules = [new NoProfanity];

            if ($field->is_required) {
                $rules[] = 'required';
            }

            return [$field->name => $rules];
        })->toArray();
    }
}
