<?php

declare(strict_types=1);

namespace App\Services;

use App\Attributes\CurrentFingerprint;
use App\Enums\FilterType;
use App\Enums\Role;
use App\Events\BlacklistMatch;
use App\Models\Blacklist;
use App\Models\Fingerprint;
use App\Models\User;
use App\Models\Whitelist;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Request;
use Throwable;

class BlacklistService
{
    public function __construct(
        protected readonly Request $request,
        #[CurrentUser]
        protected readonly ?User $user = null,
        #[CurrentFingerprint]
        protected readonly ?Fingerprint $fingerprint = null,
    ) {
        //
    }

    public function isBlacklisted(mixed $value = null, ?FilterType $filter = null): Blacklist|false
    {
        if ($this->user instanceof User && $this->user->hasAnyRole(Role::cases())) {
            return false;
        }

        if (! $filter instanceof FilterType) {
            return $this->checkAllFilters();
        }

        return $this->checkFilter($filter, $value);
    }

    protected function checkAllFilters(): Blacklist|false
    {
        if ($this->user && ($blacklist = $this->checkUserBlacklist($this->user))) {
            return $blacklist;
        }

        if ($this->fingerprint && ($blacklist = $this->checkFingerprintBlacklist($this->fingerprint))) {
            return $blacklist;
        }

        if (($ip = $this->request->ip()) && ($blacklist = $this->checkIpBlacklist($ip))) {
            return $blacklist;
        }

        return false;
    }

    protected function checkFilter(FilterType $filter, mixed $value): Blacklist|false
    {
        return match ($filter) {
            FilterType::User => $this->checkUserBlacklist($value instanceof User ? $value : User::query()->find((int) $value)),
            FilterType::Fingerprint => $this->checkFingerprintBlacklist($value instanceof Fingerprint ? $value : Fingerprint::query()->find((int) $value)),
            FilterType::IpAddress => $this->checkIpBlacklist((string) $value),
            FilterType::String => $this->checkStringBlacklist((string) $value),
        };
    }

    protected function checkUserBlacklist(?User $user): Blacklist|false
    {
        if (! $user instanceof User) {
            return false;
        }

        if ($this->isUserWhitelisted($user)) {
            return false;
        }

        $blacklist = $user->blacklist()->first();

        if ($blacklist) {
            $this->fireBlacklistMatch('User ID: '.$user->id, $blacklist);

            return $blacklist;
        }

        return false;
    }

    protected function checkFingerprintBlacklist(?Fingerprint $fingerprint): Blacklist|false
    {
        if (! $fingerprint instanceof Fingerprint) {
            return false;
        }

        if ($this->isFingerprintWhitelisted($fingerprint)) {
            return false;
        }

        $blacklist = $fingerprint->blacklist()->first();

        if ($blacklist) {
            $this->fireBlacklistMatch('Fingerprint: '.$fingerprint->id, $blacklist);

            return $blacklist;
        }

        return false;
    }

    protected function checkIpBlacklist(string $ip): Blacklist|false
    {
        if ($this->isIpWhitelisted($ip)) {
            return false;
        }

        $blacklist = Blacklist::query()
            ->where('filter', FilterType::IpAddress)
            ->where('content', $ip)
            ->first();

        if ($blacklist) {
            $this->fireBlacklistMatch('IP Address: '.$ip, $blacklist);

            return $blacklist;
        }

        return false;
    }

    protected function checkStringBlacklist(string $value): Blacklist|false
    {
        if ($this->isStringWhitelisted($value)) {
            return false;
        }

        $blacklists = Blacklist::query()
            ->where('filter', FilterType::String)
            ->get();

        foreach ($blacklists as $blacklist) {
            if ($this->matchesStringBlacklist($value, $blacklist)) {
                $this->fireBlacklistMatch($value, $blacklist);

                return $blacklist;
            }
        }

        return false;
    }

    protected function matchesStringBlacklist(string $value, Blacklist $blacklist): bool
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
        $items = array_map(trim(...), explode(',', $content));
        $lowerValue = strtolower($value);

        return array_any($items, fn ($item): bool => str_contains($lowerValue, strtolower((string) $item)));
    }

    protected function isUserWhitelisted(User $user): bool
    {
        return Whitelist::query()
            ->whereMorphedTo('resource', $user)
            ->where('filter', FilterType::User)
            ->exists();
    }

    protected function isFingerprintWhitelisted(Fingerprint $fingerprint): bool
    {
        return Whitelist::query()
            ->whereMorphedTo('resource', $fingerprint)
            ->where('filter', FilterType::Fingerprint)
            ->exists();
    }

    protected function isIpWhitelisted(string $ip): bool
    {
        return Whitelist::query()
            ->where('content', $ip)
            ->where('filter', FilterType::IpAddress)
            ->exists();
    }

    protected function isStringWhitelisted(string $value): bool
    {
        return Whitelist::query()
            ->where('content', $value)
            ->where('filter', FilterType::String)
            ->exists();
    }

    protected function fireBlacklistMatch(string $content, Blacklist $blacklist): void
    {
        event(new BlacklistMatch(
            content: $content,
            blacklist: $blacklist,
            user: $this->user
        ));
    }
}
