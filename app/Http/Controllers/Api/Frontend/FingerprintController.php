<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Frontend;

use App\Data\FingerprintData;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\Fingerprint;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class FingerprintController extends Controller
{
    public function __construct(
        #[CurrentUser]
        private readonly ?User $user = null,
    ) {
        //
    }

    public function __invoke(Request $request): ApiResource
    {
        $request->validate([
            'fingerprint_id' => 'required|string|max:255',
            'request_id' => 'required|string|max:255',
        ]);

        $fingerprint = Fingerprint::trackFingerprint(
            userId: $this->user->id ?? null,
            fingerprintId: $request->input('fingerprint_id'),
            requestId: $request->input('request_id'),
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
        );
    }
}
