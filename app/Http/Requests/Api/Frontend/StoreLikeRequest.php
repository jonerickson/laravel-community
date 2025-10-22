<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Frontend;

use App\Enums\WarningConsequenceType;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;

class StoreLikeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:post,comment'],
            'id' => ['required', 'integer'],
            'emoji' => ['required'],
        ];
    }

    public function resolveLikeable(): Post|Comment|null
    {
        return match ($this->input('type')) {
            'post' => Post::find($this->integer('id')),
            'comment' => Comment::find($this->integer('id')),
            default => null,
        };
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
