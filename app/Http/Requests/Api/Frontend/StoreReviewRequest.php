<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Frontend;

use App\Enums\WarningConsequenceType;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Product;
use App\Rules\BlacklistRule;
use App\Rules\NoProfanity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'commentable_type' => ['required', 'string', 'in:post,comment,product'],
            'commentable_id' => ['required', 'integer'],
            'content' => ['required', 'string', new NoProfanity, new BlacklistRule],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ];
    }

    public function resolveCommentable(): Post|Comment|Product
    {
        $commentableType = match ($this->input('commentable_type')) {
            'post' => Post::class,
            'comment' => Comment::class,
            'product' => Product::class,
        };

        return $commentableType::findOrFail($this->integer('commentable_id'));
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

            if ($this->input('commentable_type') !== 'product' || empty($this->input('rating'))) {
                return;
            }

            $commentable = $this->resolveCommentable();

            if (! $commentable instanceof Product) {
                return;
            }

            $existingReview = $commentable->reviews()
                ->whereBelongsTo(Auth::user(), 'author')
                ->exists();

            if ($existingReview) {
                $validator->errors()->add(
                    'content',
                    'You have already submitted a review for this product.'
                );
            }
        });
    }
}
