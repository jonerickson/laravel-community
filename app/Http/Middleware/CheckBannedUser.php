<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\BannedException;
use App\Models\Fingerprint;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CheckBannedUser
{
    /**
     * @throws Throwable
     */
    public function handle(Request $request, Closure $next): Response
    {
        $fingerprintId = $request->header('X-Fingerprint-ID') ?? $request->cookie('fingerprint_id');

        $fingerprints = Fingerprint::query()
            ->when($request->user(), fn (Builder $query, User $user) => $query->whereBelongsTo($user))
            ->when($fingerprintId, fn (Builder $query, string $fingerprintId) => $query->where('fingerprint_id', $fingerprintId))
            ->get();

        foreach ($fingerprints as $fingerprint) {
            throw_if($fingerprint->isBanned(), new BannedException(
                fingerprint: $fingerprint,
            ));
        }

        return $next($request);
    }
}
