<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Frontend;

use App\Models\Forum;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StorePinRequest extends FormRequest
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
            'type' => ['required', 'string', 'in:post,topic'],
            'id' => ['required', 'integer'],
        ];
    }

    public function resolvePinnable(): Topic|Post
    {
        return match ($this->input('type')) {
            'post' => Post::findOrFail($this->integer('id')),
            'topic' => Topic::findOrFail($this->integer('id')),
        };
    }

    public function resolveAuthorizable(): ?Forum
    {
        $pinnable = $this->resolvePinnable();

        return match (true) {
            $pinnable instanceof Post => $pinnable->topic?->forum,
            $pinnable instanceof Topic => $pinnable->forum,
            default => null,
        };
    }
}
