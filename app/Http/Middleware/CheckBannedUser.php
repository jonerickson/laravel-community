<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\UserFingerprint;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckBannedUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $fingerprintId = $request->header('X-Fingerprint-ID') ?? $request->cookie('fingerprint_id');

        if ($fingerprintId) {
            $fingerprint = UserFingerprint::where('fingerprint_id', $fingerprintId)->first();
            if ($fingerprint && $fingerprint->isBanned()) {
                if (! $request->routeIs('banned')) {
                    if (Auth::check()) {
                        return redirect()->route('banned');
                    }

                    return response()->view('errors.banned', [
                        'message' => 'This device has been banned from accessing the site.',
                    ], 403);
                }
            }
        }

        return $next($request);
    }
}
