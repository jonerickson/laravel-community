<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Frontend;

use App\Models\Forum;
use App\Models\Topic;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreFollowRequest extends FormRequest
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
            'type' => ['required', 'string', 'in:forum,topic'],
            'id' => ['required', 'integer'],
        ];
    }

    public function resolveFollowable(): Forum|Topic
    {
        return match ($this->input('type')) {
            'forum' => Forum::findOrFail($this->integer('id')),
            'topic' => Topic::findOrFail($this->integer('id')),
        };
    }
}
