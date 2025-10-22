<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Frontend;

use App\Models\Post;
use App\Models\Topic;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StorePinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->can('pin', $this->resolvePinnable());
    }

    public function rules(): array
    {
        return [
            'topic_id' => ['sometimes', 'required', 'exists:topics,id'],
            'post_id' => ['sometimes', 'required', 'exists:posts,id'],
        ];
    }

    public function resolvePinnable(): Topic|Post|null
    {
        return match (true) {
            $this->has('topic_id') => Topic::findOrFail($this->integer('topic_id')),
            $this->has('post_id') => Post::findOrFail($this->integer('post_id')),
            default => null,
        };
    }
}
