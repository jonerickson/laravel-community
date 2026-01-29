<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\UserIntegration;
use Carbon\Carbon;
use Illuminate\Container\Attributes\Config;

class JwtService
{
    public function __construct(
        #[Config('app.key')]
        protected string $appKey,
    ) {}

    /**
     * Generate a JWT for the given user.
     *
     * @param  array<string, mixed>  $additionalClaims
     */
    public function generateForUser(User $user, ?string $secret = null, array $additionalClaims = [], int $expiresIn = 300): string
    {
        $secret = $secret ?: $this->appKey;

        $claims = [
            'sub' => (string) $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'iat' => Carbon::now()->getTimestamp(),
            'exp' => Carbon::now()->getTimestamp() + $expiresIn,
            ...$additionalClaims,
        ];

        $user->loadMissing('integrations')->integrations->each(function (UserIntegration $integration) use (&$claims): void {
            $claims[$integration->provider] = $integration->provider_id;
        });

        return $this->encode($claims, $secret);
    }

    /**
     * Generate a JWT with custom claims.
     *
     * @param  array<string, mixed>  $claims
     */
    public function generate(array $claims, ?string $secret = null, int $expiresIn = 300): string
    {
        $secret = $secret ?: $this->appKey;

        $claims = [
            'iat' => Carbon::now()->getTimestamp(),
            'exp' => Carbon::now()->getTimestamp() + $expiresIn,
            ...$claims,
        ];

        return $this->encode($claims, $secret);
    }

    /**
     * Encode claims into a JWT.
     *
     * @param  array<string, mixed>  $claims
     */
    private function encode(array $claims, string $secret): string
    {
        $header = $this->base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payload = $this->base64UrlEncode(json_encode($claims));

        $signature = hash_hmac('sha256', $header.'.'.$payload, $secret, true);
        $base64Signature = $this->base64UrlEncode($signature);

        return $header.'.'.$payload.'.'.$base64Signature;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
