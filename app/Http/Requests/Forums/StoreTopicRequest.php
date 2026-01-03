<?php

declare(strict_types=1);

namespace App\Http\Requests\Forums;

use App\Enums\WarningConsequenceType;
use App\Rules\BlacklistRule;
use App\Rules\NoProfanity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;
use Override;

class StoreTopicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:2', 'max:255', new NoProfanity, new BlacklistRule],
            'content' => ['required', 'string', 'min:2', 'max:10000', new NoProfanity, new BlacklistRule],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function messages(): array
    {
        return [
            'title.required' => 'Please provide a title for your topic.',
            'title.max' => 'The title cannot be longer than 255 characters.',
            'content.required' => 'Please provide content for your topic.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (Auth::user()->active_consequence_type === WarningConsequenceType::PostRestriction) {
                $validator->errors()->add(
                    'content',
                    'You have been restricted from posting.'
                );
            }
        });
    }
}
