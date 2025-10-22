<?php

declare(strict_types=1);

namespace App\Http\Requests\Blog;

use App\Enums\WarningConsequenceType;
use App\Rules\BlacklistRule;
use App\Rules\NoProfanity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;

class UpdateCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->can('update', $this->route('comment'));
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', new NoProfanity, new BlacklistRule],
            'parent_id' => ['nullable', 'int'],
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
