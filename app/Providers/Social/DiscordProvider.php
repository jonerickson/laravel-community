<?php

declare(strict_types=1);

namespace App\Providers\Social;

use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;

class DiscordProvider extends AbstractProvider
{
    protected $scopes = ['email', 'identify', 'guilds.join'];

    protected $scopeSeparator = ' ';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://discord.com/api/oauth2/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://discord.com/api/oauth2/token';
    }

    /**
     * @throws GuzzleException|JsonException
     */
    protected function getUserByToken($token): mixed
    {
        $response = $this->getHttpClient()->get('https://discord.com/api/users/@me', [
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
            'id' => $user['id'],
            'name' => $user['username'],
            'email' => $user['email'] ?? null,
        ]);
    }
}
