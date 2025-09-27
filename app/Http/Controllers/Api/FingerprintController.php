<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Data\FingerprintData;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\Fingerprint;
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

        $fingerprint = Fingerprint::trackFingerprint(
            userId: Auth::id(),
            fingerprintId: $request->input('fingerprint_id'),
            fingerprintData: $request->input('fingerprint_data'),
            ipAddress: $request->ip(),
            userAgent: $request->userAgent()
        )->refresh();

        Cookie::queue(
            key: 'fingerprint_id',
            value: $fingerprint->fingerprint_id,
        );

        $fingerprintData = FingerprintData::from([
            'fingerprintId' => $fingerprint->fingerprint_id,
            'firstSeen' => $fingerprint->first_seen_at,
            'lastSeen' => $fingerprint->last_seen_at,
        ]);

        return ApiResource::success(
            resource: $fingerprintData,
            message: 'Fingerprint tracked successfully.'
        );
    }
}
