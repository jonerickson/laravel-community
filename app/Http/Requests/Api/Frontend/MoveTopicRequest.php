<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Frontend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class MoveTopicRequest extends FormRequest
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
            'topic_id' => ['required', 'integer', 'exists:topics,id'],
            'target_forum_id' => ['required', 'integer', 'exists:forums,id'],
        ];
    }
}
