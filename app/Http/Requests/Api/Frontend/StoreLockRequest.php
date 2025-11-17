<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Frontend;

use App\Models\Topic;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreLockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->can('lock', $this->resolveLockable());
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'topic_id' => ['required', 'exists:topics,id'],
        ];
    }

    public function resolveLockable(): Topic
    {
        return Topic::findOrFail($this->integer('topic_id'));
    }
}
