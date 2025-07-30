<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\UserFingerprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class BannedController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = Auth::user();
        $fingerprintId = $request->header('X-Fingerprint-ID') ?? $request->cookie('fingerprint_id');

        $fingerprint = null;
        if ($fingerprintId) {
            $fingerprint = UserFingerprint::where('fingerprint_id', $fingerprintId)
                ->whereBelongsTo($user)
                ->with('bannedBy')
                ->first();
        }

        return Inertia::render('banned', [
            'user' => $user,
            'fingerprint' => $fingerprint,
            'banReason' => $fingerprint?->ban_reason,
            'bannedAt' => $fingerprint?->banned_at,
            'bannedBy' => $fingerprint?->bannedBy?->name,
        ]);
    }
}
