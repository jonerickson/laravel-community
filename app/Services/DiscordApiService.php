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
    public function getUserRoleIds(string $discordUserId): Collection
    {
        $response = $this->makeRequest('get', "/guilds/$this->guildId/members/$discordUserId");

        return collect($response->json('roles'));
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function listRoles(): Collection
    {
        $response = $this->makeRequest('get', "/guilds/$this->guildId/roles");

        return collect($response->json());
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function addRole(string $discordUserId, string $roleId): void
    {
        $this->makeRequest('put', "/guilds/$this->guildId/members/$discordUserId/roles/$roleId");
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function removeRole(string $discordUserId, string $roleId): void
    {
        $this->makeRequest('delete', "/guilds/$this->guildId/members/$discordUserId/roles/$roleId");
    }

    /**
     * Make a request to the Discord API with rate limit and error handling.
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    protected function makeRequest(string $method, string $url, array $options = []): Response
    {
        return $this->client()
            ->retry($this->maxRetries, 0, function (Exception $exception) use ($url): bool {
                if ($exception instanceof ConnectionException) {
                    $this->log->warning('Discord API connection error, retrying...', [
                        'url' => $url,
                        'exception' => $exception->getMessage(),
                    ]);

                    return true;
                }

                if ($exception instanceof RequestException) {
                    $response = $exception->response;

                    if ($response->status() === 429) {
                        $retryAfter = $response->header('Retry-After');

                        $this->log->warning('Discord API rate limit hit, waiting before retry', [
                            'url' => $url,
                            'retry_after' => $retryAfter,
                        ]);

                        Sleep::until(Carbon::now()->addMilliseconds($retryAfter));

                        return true;
                    }

                    if ($response->serverError()) {
                        $this->log->warning('Discord API server error, retrying...', [
                            'url' => $url,
                            'status' => $response->status(),
                        ]);

                        return true;
                    }

                    $this->log->error('Discord API client error', [
                        'url' => $url,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);

                    return false;
                }

                return false;
            })
            ->{$method}($url, $options);
    }

    protected function client(): PendingRequest|Factory
    {
        return Http::withToken($this->botToken, 'Bot')
            ->baseUrl($this->baseUrl);
    }
}
