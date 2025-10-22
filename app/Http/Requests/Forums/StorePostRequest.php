<?php

declare(strict_types=1);

namespace App\Http\Requests\Forums;

use App\Enums\WarningConsequenceType;
use App\Models\Post;
use App\Rules\BlacklistRule;
use App\Rules\NoProfanity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;
use Override;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->can('create', Post::class);
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', new NoProfanity, new BlacklistRule],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'content.required' => 'Please provide a reply before posting.',
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
