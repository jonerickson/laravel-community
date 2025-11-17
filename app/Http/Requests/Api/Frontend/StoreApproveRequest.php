<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Frontend;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreApproveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->can('approve', $this->resolveApprovable());
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

    public function resolveApprovable(): Post|Comment|null
    {
        return match ($this->input('type')) {
            'post' => Post::find($this->integer('id')),
            'comment' => Comment::find($this->integer('id')),
            default => null,
        };
    }
}
