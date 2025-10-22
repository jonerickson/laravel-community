<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Frontend;

use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StorePublishRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->can('publish', $this->resolvePublishable());
    }

    public function rules(): array
    {
        return [
            'post_id' => ['required', 'exists:posts,id'],
        ];
    }

    public function resolvePublishable(): Post
    {
        return Post::findOrFail($this->integer('post_id'));
    }
}
