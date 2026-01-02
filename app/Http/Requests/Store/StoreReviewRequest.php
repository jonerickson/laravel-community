<?php

declare(strict_types=1);

namespace App\Http\Requests\Store;

use App\Enums\WarningConsequenceType;
use App\Models\Product;
use App\Rules\BlacklistRule;
use App\Rules\NoProfanity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;
use Override;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:10', 'max:1000', new NoProfanity, new BlacklistRule],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function messages(): array
    {
        return [
            'content.required' => 'Please provide a review.',
            'content.min' => 'The review must be at least 10 characters.',
            'content.max' => 'The review cannot exceed 1,000 characters.',
            'rating.required' => 'Please provide a rating.',
            'rating.integer' => 'The rating must be a valid number.',
            'rating.min' => 'The rating must be at least 1 star.',
            'rating.max' => 'The rating cannot exceed 5 stars.',
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

            $product = $this->route('subscription');

            if (! $product instanceof Product) {
                return;
            }

            $existingReview = $product->reviews()
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
