<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Frontend;

use App\Models\Announcement;
use App\Models\Forum;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:topic,post,forum,announcement'],
            'id' => ['required', 'integer'],
        ];
    }

    public function resolveReadable(): Topic|Post|Forum|Announcement|null
    {
        return match ($this->input('type')) {
            'topic' => Topic::find($this->integer('id')),
            'post' => Post::find($this->integer('id')),
            'forum' => Forum::find($this->integer('id')),
            'announcement' => Announcement::find($this->integer('id')),
            default => null,
        };
    }
}
