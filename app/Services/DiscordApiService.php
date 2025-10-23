<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Container\Attributes\Config;
use Illuminate\Container\Attributes\Log;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Psr\Log\LoggerInterface;

class DiscordApiService
{
    protected string $baseUrl = 'https://discord.com/api/v10/';

    protected int $maxRetries = 3;

    public function __construct(
        #[Config('services.discord.guild_id')]
        protected ?string $guildId,
        #[Config('services.discord.bot_token')]
        protected ?string $botToken,
        #[Log]
        protected LoggerInterface $log,
    ) {
        //
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function isUserInServer(string $discordUserId): bool
    {
        $response = $this->makeRequest('get', "/guilds/$this->guildId/members/$discordUserId");

        return ! is_null($response);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function addUserToServer(string $discordUserId, string $accessToken, array $roleIds = []): bool
    {
        $payload = [
            'access_token' => $accessToken,
        ];

        if ($roleIds !== []) {
            $payload['roles'] = $roleIds;
        }

        $response = $this->makeRequest('put', "/guilds/$this->guildId/members/$discordUserId", $payload);

        return ! is_null($response);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function removeUserFromServer(string $discordUserId): bool
    {
        $response = $this->makeRequest('delete', "/guilds/$this->guildId/members/$discordUserId");

        return ! is_null($response);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getUserRoleIds(string $discordUserId): Collection
    {
        $response = $this->makeRequest('get', "/guilds/$this->guildId/members/$discordUserId");

        if (is_null($response)) {
            return new Collection;
        }

        return collect($response->json('roles'));
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function listRoles(): Collection
    {
        $response = $this->makeRequest('get', "/guilds/$this->guildId/roles");

        if (is_null($response)) {
            return new Collection;
        }

        return collect($response->json());
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function addRole(string $discordUserId, string $roleId): bool
    {
        $response = $this->makeRequest('put', "/guilds/$this->guildId/members/$discordUserId/roles/$roleId");

        return ! is_null($response);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function removeRole(string $discordUserId, string $roleId): bool
    {
        $response = $this->makeRequest('delete', "/guilds/$this->guildId/members/$discordUserId/roles/$roleId");

        return ! is_null($response);
    }

    /**
     * Make a request to the Discord API with rate limit and error handling.
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    protected function makeRequest(string $method, string $url, array $options = []): ?Response
    {
        try {
            return $this->client()
                ->retry($this->maxRetries, 0, function (Exception $exception): bool {
                    if ($exception instanceof ConnectionException) {
                        return true;
                    }

                    if ($exception instanceof RequestException) {
                        $response = $exception->response;
                        if ($response->status() === 429) {
                            $retryAfter = $response->header('Retry-After');

                            Sleep::until(Carbon::now()->addMilliseconds($retryAfter));

                            return true;
                        }

                        return $response->serverError();
                    }

                    return false;
                })
                ->{$method}($url, $options);
        } catch (ConnectionException $exception) {
            $this->log->error('Discord API connection failed after retries', [
                'url' => $url,
                'method' => $method,
                'exception' => $exception->getMessage(),
            ]);
        } catch (RequestException $exception) {
            $this->log->error('Discord API request failed', [
                'url' => $url,
                'method' => $method,
                'status' => $exception->response->status(),
                'body' => $exception->response->body(),
            ]);
        } catch (Exception $exception) {
            $this->log->error('Unexpected error during Discord API request', [
                'url' => $url,
                'method' => $method,
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
        }

        return null;
    }

    protected function client(): PendingRequest|Factory
    {
        return Http::withToken($this->botToken, 'Bot')
            ->baseUrl($this->baseUrl);
    }
}
