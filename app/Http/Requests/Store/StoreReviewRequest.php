<?php

declare(strict_types=1);

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
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
            'content' => ['required', 'string', 'min:10', 'max:1000'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'content.required' => 'Please provide a review.',
            'content.min' => 'Review must be at least 10 characters.',
            'content.max' => 'Review cannot exceed 1000 characters.',
            'rating.required' => 'Please provide a rating.',
            'rating.integer' => 'Rating must be a valid number.',
            'rating.min' => 'Rating must be at least 1 star.',
            'rating.max' => 'Rating cannot exceed 5 stars.',
        ];
    }
}
