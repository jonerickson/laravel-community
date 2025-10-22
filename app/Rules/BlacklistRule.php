<?php

declare(strict_types=1);

namespace App\Rules;

use App\Events\BlacklistMatch;
use App\Models\Blacklist;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Throwable;

class BlacklistRule implements ValidationRule
{
    public function __construct(
        protected ?string $message = null
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $blacklists = Cache::remember('blacklist', 3600, fn () => Blacklist::all(['id', 'content', 'is_regex']));

        foreach ($blacklists as $blacklist) {
            if ($this->isBlacklisted($value, $blacklist)) {
                BlacklistMatch::dispatch($value, $blacklist, Auth::user());

                $message = $this->message ?? 'The :attribute contains prohibited content.';
                $fail($message);

                return;
            }
        }
    }

    protected function isBlacklisted(string $value, Blacklist $blacklist): bool
    {
        if ($blacklist->is_regex) {
            return $this->matchesRegexPattern($value, $blacklist->content);
        }

        return $this->matchesExactContent($value, $blacklist->content);
    }

    protected function matchesRegexPattern(string $value, string $pattern): bool
    {
        try {
            return preg_match($pattern, $value) === 1;
        } catch (Throwable) {
            return false;
        }
    }

    protected function matchesExactContent(string $value, string $content): bool
    {
        $items = array_map('trim', explode(',', $content));
        $lowerValue = strtolower($value);

        return array_any($items, fn ($item): bool => str_contains($lowerValue, strtolower($item)));
    }
}
