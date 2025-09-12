<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\UserFingerprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class FingerprintController extends Controller
{
    public function __invoke(Request $request): ApiResource
    {
        $request->validate([
            'fingerprint_id' => 'required|string|max:255',
            'fingerprint_data' => 'nullable|array',
        ]);

        $fingerprint = UserFingerprint::trackFingerprint(
            userId: Auth::id(),
            fingerprintId: $request->input('fingerprint_id'),
            fingerprintData: $request->input('fingerprint_data'),
            ipAddress: $request->ip(),
            userAgent: $request->userAgent()
        )->refresh();

        if ($fingerprint->isBanned()) {
            return ApiResource::error(
                message: 'Access denied. This device has been banned.',
                status: 403
            );
        }

        Cookie::queue(
            key: 'fingerprint_id',
            value: $fingerprint->fingerprint_id,
        );

        return ApiResource::success(
            resource: [
                'fingerprint_id' => $fingerprint->fingerprint_id,
                'first_seen' => $fingerprint->first_seen_at,
                'last_seen' => $fingerprint->last_seen_at,
            ],
            message: 'Fingerprint tracked successfully.'
        );
    }
}
