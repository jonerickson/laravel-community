<?php

declare(strict_types=1);

namespace App\Providers\Social;

use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;

class RobloxProvider extends AbstractProvider
{
    protected $scopes = ['openid', 'profile'];

    protected $scopeSeparator = ' ';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://apis.roblox.com/oauth/v1/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://apis.roblox.com/oauth/v1/token';
    }

    /**
     * @throws GuzzleException|JsonException
     */
    protected function getUserByToken($token): mixed
    {
        $response = $this->getHttpClient()->get('https://apis.roblox.com/oauth/v1/userinfo', [
            'headers' => [
                'cache-control' => 'no-cache',
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }

    protected function mapUserToObject(array $user): User
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['sub'],
            'name' => $user['preferred_username'] ?? $user['name'] ?? $user['nickname'],
            'avatar' => $user['picture'] ?? null,
        ]);
    }
}
