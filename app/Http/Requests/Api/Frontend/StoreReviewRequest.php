<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Frontend;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->can('create', Comment::class);
    }

    public function rules(): array
    {
        return [
            'commentable_type' => ['required', 'string', 'in:post,comment,product'],
            'commentable_id' => ['required', 'integer'],
            'content' => ['nullable', 'string'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (empty($this->input('content')) && empty($this->input('rating'))) {
                $validator->errors()->add(
                    'content',
                    'Either content or rating must be provided.'
                );

                return;
            }

            if ($this->input('commentable_type') !== 'product' || empty($this->input('rating'))) {
                return;
            }

            $commentableType = match ($this->input('commentable_type')) {
                'post' => Post::class,
                'comment' => Comment::class,
                'product' => Product::class,
            };

            $commentable = $commentableType::find($this->input('commentable_id'));

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
