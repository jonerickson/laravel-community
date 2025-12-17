<?php

declare(strict_types=1);

namespace App\Support\Passport;

use App\Models\User;
use Carbon\CarbonImmutable;
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
use Override;

class IdTokenResponse extends BearerTokenResponse
{
    protected CryptKeyInterface $privateKey;

    public function __construct()
    {
        if (file_exists(Passport::keyPath('oauth-private.key'))) {
            $this->privateKey = new CryptKey('file://'.Passport::keyPath('oauth-private.key'), null, Passport::$validateKeyPermissions);
        }
    }

    #[Override]
    protected function getExtraParams(AccessTokenEntityInterface $accessToken): array
    {
        if (! $this->isOpenIdRequest($accessToken)) {
            return [];
        }

        $user = $this->getUserEntity($accessToken);

        if (is_null($user)) {
            return [];
        }

        return [
            'id_token' => $this->makeIdToken($accessToken, $user),
        ];
    }

    protected function makeIdToken(AccessToken $accessToken, User $user): string
    {
        $privateKeyContents = $this->privateKey->getKeyContents();

        $config = Configuration::forAsymmetricSigner(
            new Sha256,
            InMemory::plainText($privateKeyContents, $this->privateKey->getPassPhrase() ?? ''),
            InMemory::plainText('empty', 'empty')
        );

        $builder = $config->builder()
            ->permittedFor($accessToken->getClient()->getIdentifier())
            ->identifiedBy($accessToken->getIdentifier())
            ->issuedAt(CarbonImmutable::now())
            ->canOnlyBeUsedAfter(CarbonImmutable::now())
            ->expiresAt($accessToken->getExpiryDateTime())
            ->relatedTo($accessToken->getUserIdentifier())
            ->withClaim('name', $user->name);

        if ($this->hasScope($accessToken, 'email')) {
            $builder = $builder->withClaim('email', $user->email)
                ->withClaim('email_verified', $user->email_verified_at !== null);
        }

        if ($this->hasScope($accessToken, 'profile')) {
            $builder = $builder->withClaim('picture', $user->avatar_url);
        }

        return $builder
            ->getToken($config->signer(), $config->signingKey())
            ->toString();
    }

    protected function isOpenIdRequest(AccessToken $accessToken): bool
    {
        return array_any(
            $accessToken->getScopes(),
            fn (ScopeEntityInterface $scope): bool => $scope->getIdentifier() === 'openid'
        );
    }

    protected function hasScope(AccessToken $accessToken, string $scope): bool
    {
        return array_any(
            $accessToken->getScopes(),
            fn ($tokenScope): bool => $tokenScope->getIdentifier() === $scope
        );
    }

    protected function getUserEntity(AccessToken $accessToken): ?User
    {
        return User::find($accessToken->getUserIdentifier());
    }
}
