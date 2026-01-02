<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Frontend;

use App\Models\Comment;
use App\Models\Forum;
use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreApproveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * @return array<string, string[]>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:post,comment'],
            'id' => ['required', 'integer'],
        ];
    }

    public function resolveApprovable(): Post|Comment
    {
        return match ($this->input('type')) {
            'post' => Post::findOrFail($this->integer('id')),
            'comment' => Comment::findOrFail($this->integer('id')),
        };
    }

    public function resolveAuthorizable(): ?Forum
    {
        $approvable = $this->resolveApprovable();

        return match (true) {
            $approvable instanceof Post => $approvable->topic?->forum,
            default => null,
        };
    }
}
