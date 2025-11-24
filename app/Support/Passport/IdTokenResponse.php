<?php

declare(strict_types=1);

namespace App\Support\Passport;

use App\Models\User;
use DateTimeImmutable;
use Laravel\Passport\Bridge\AccessToken;
use Laravel\Passport\Passport;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\CryptKeyInterface;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\ResponseTypes\BearerTokenResponse;

class IdTokenResponse extends BearerTokenResponse
{
    protected CryptKeyInterface $privateKey;

    public function __construct()
    {
        $this->privateKey = new CryptKey('file://'.Passport::keyPath('oauth-private.key'), null, Passport::$validateKeyPermissions);
    }

    protected function getExtraParams(AccessTokenEntityInterface $accessToken): array
    {
        if (! $this->isOpenIdRequest($accessToken)) {
            return [];
        }

        return [
            'id_token' => $this->makeIdToken($accessToken),
        ];
    }

    protected function isOpenIdRequest(AccessToken $accessToken): bool
    {
        return array_any(
            $accessToken->getScopes(),
            fn (ScopeEntityInterface $scope) => $scope->getIdentifier() === 'openid'
        );
    }

    protected function makeIdToken(AccessToken $accessToken): string
    {
        $privateKeyContents = $this->privateKey->getKeyContents();

        $config = Configuration::forAsymmetricSigner(
            new Sha256,
            InMemory::plainText($privateKeyContents, $this->privateKey->getPassPhrase() ?? ''),
            InMemory::base64Encoded('empty', 'empty')
        );

        $now = new DateTimeImmutable;
        $user = $this->getUserEntity($accessToken);

        $builder = $config->builder()
            ->issuedBy(config('app.url'))
            ->permittedFor($accessToken->getClient()->getIdentifier())
            ->identifiedBy($accessToken->getIdentifier())
            ->issuedAt($now)
            ->expiresAt($accessToken->getExpiryDateTime())
            ->relatedTo((string) $user->id)
            ->withClaim('name', $user->name);

        if ($this->hasScope($accessToken, 'email')) {
            $builder->withClaim('email', $user->email);
            $builder->withClaim('email_verified', $user->email_verified_at !== null);
        }

        if ($this->hasScope($accessToken, 'profile')) {
            $builder->withClaim('avatar_url', $user->avatar_url);
            $builder->withClaim('reference_id', $user->reference_id);
        }

        return $builder
            ->getToken($config->signer(), $config->signingKey())
            ->toString();
    }

    protected function hasScope(AccessToken $accessToken, string $scope): bool
    {
        return array_any(
            $accessToken->getScopes(),
            fn ($tokenScope) => $tokenScope->getIdentifier() === $scope
        );
    }

    protected function getUserEntity(AccessToken $accessToken): User
    {
        return User::find($accessToken->getUserIdentifier());
    }
}
