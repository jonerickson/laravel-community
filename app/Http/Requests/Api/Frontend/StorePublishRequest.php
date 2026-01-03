<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Frontend;

use App\Models\Forum;
use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StorePublishRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:post'],
            'id' => ['required', 'integer'],
        ];
    }

    public function resolvePublishable(): Post
    {
        return match ($this->input('type')) {
            'post' => Post::findOrFail($this->integer('id')),
        };
    }

    public function resolveAuthorizable(): ?Forum
    {
        $publishable = $this->resolvePublishable();

        return match (true) {
            $publishable instanceof Post => $publishable->topic?->forum,
            default => null,
        };
    }
}
