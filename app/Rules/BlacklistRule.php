<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\FilterType;
use App\Services\BlacklistService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class BlacklistRule implements ValidationRule
{
    use NormalizeStringHelpers;

    public string $message;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $value = $this->normalize($value);

        $blacklistService = app(BlacklistService::class);

        if ($blacklistService->isBlacklisted($value, FilterType::String)) {
            $message = $this->message ?? 'The :attribute contains prohibited content.';
            $fail($message);
        }
    }
}
