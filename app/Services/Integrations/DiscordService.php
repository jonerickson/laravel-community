<?php

declare(strict_types=1);

namespace App\Services\Integrations;

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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Illuminate\Support\Uri;
use Psr\Log\LoggerInterface;

class DiscordService
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
    public function getPresenceCount(): int
    {
        $response = $this->makeRequest('get', Uri::of(sprintf('/guilds/%s/preview', $this->guildId))->withQuery(['with_counts' => 'true'])->value());

        if (is_null($response)) {
            return 0;
        }

        return (int) $response->json('approximate_presence_count') ?? 0;
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getMemberCount(): int
    {
        $response = $this->makeRequest('get', Uri::of(sprintf('/guilds/%s/preview', $this->guildId))->withQuery(['with_counts' => 'true'])->value());

        if (is_null($response)) {
            return 0;
        }

        return (int) $response->json('approximate_member_count') ?? 0;
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function isUserInServer(string $discordUserId): bool
    {
        $response = $this->makeRequest('get', sprintf('/guilds/%s/members/%s', $this->guildId, $discordUserId));

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

        $response = $this->makeRequest('put', sprintf('/guilds/%s/members/%s', $this->guildId, $discordUserId), $payload);

        return ! is_null($response);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function removeUserFromServer(string $discordUserId): bool
    {
        $response = $this->makeRequest('delete', sprintf('/guilds/%s/members/%s', $this->guildId, $discordUserId));

        return ! is_null($response);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function listRoles(): Collection
    {
        $response = $this->makeRequest('get', sprintf('/guilds/%s/roles', $this->guildId));

        if (is_null($response)) {
            return new Collection;
        }

        return collect($response->json());
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getUserRoleIds(string $discordUserId): Collection
    {
        $response = $this->makeRequest('get', sprintf('/guilds/%s/members/%s', $this->guildId, $discordUserId));

        if (is_null($response)) {
            return new Collection;
        }

        return collect($response->json('roles'));
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function addRole(string $discordUserId, string $roleId): bool
    {
        $response = $this->makeRequest('put', sprintf('/guilds/%s/members/%s/roles/%s', $this->guildId, $discordUserId, $roleId));

        $this->resetCachedUserRoles($discordUserId);

        return ! is_null($response);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function removeRole(string $discordUserId, string $roleId): bool
    {
        $response = $this->makeRequest('delete', sprintf('/guilds/%s/members/%s/roles/%s', $this->guildId, $discordUserId, $roleId));

        $this->resetCachedUserRoles($discordUserId);

        return ! is_null($response);
    }

    public function getCachedUserRoleIds(string $discordUserId): Collection
    {
        return Cache::remember('discord_user_roles.'.$discordUserId, now()->addMinutes(5), fn (): Collection => $this->getUserRoleIds($discordUserId));
    }

    public function resetCachedUserRoles(string $discordUserId): void
    {
        Cache::forget('discord_user_roles.'.$discordUserId);
    }

    public function getCachedGuildRoles(): Collection
    {
        return Cache::remember('discord_guild_roles', now()->addHour(), function (): Collection {
            $roles = $this->listRoles();

            return $roles->mapWithKeys(fn (array $role): array => [$role['id'] => $role['name']]);
        });
    }

    /**
     * Make a request to the Discord API with rate limit and error handling.
     *
     * @param  array<string, string>|array<string, non-empty-array>  $options
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
            ->withUserAgent(config('app.name'))
            ->acceptJson()
            ->baseUrl($this->baseUrl);
    }
}
