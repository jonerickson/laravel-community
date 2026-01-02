<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Frontend;

use App\Models\Forum;
use App\Models\Topic;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreLockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->can('lock', $this->resolveAuthorizable());
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:topic'],
            'id' => ['required', 'integer'],
        ];
    }

    public function resolveLockable(): Topic
    {
        return match ($this->input('type')) {
            'topic' => Topic::findOrFail($this->integer('id')),
        };
    }

    private function resolveAuthorizable(): ?Forum
    {
        $lockable = $this->resolveLockable();

        return match (true) {
            $lockable instanceof Topic => $lockable->forum,
            default => null,
        };
    }
}
