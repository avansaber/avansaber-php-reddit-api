<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Http;

use Avansaber\RedditApi\Auth\Token;
use Avansaber\RedditApi\Auth\TokenRefresher;
use Avansaber\RedditApi\Exceptions\RedditApiException;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

final class AutoRefreshingClient
{
    public function __construct(
        private readonly RedditApiClient $client,
        private readonly TokenRefresher $refresher,
        private Token $token,
        private readonly ?LoggerInterface $logger = null,
    ) {
        $this->client->withToken($this->token->accessToken);
    }

    /**
     * Performs a request, refreshes on 401 once, persists and retries.
     *
     * @param array<string, string|int|bool> $query
     * @param array<string, string> $headers
     */
    public function request(string $method, string $uri, array $query = [], array $headers = []): string
    {
        try {
            return $this->client->request($method, $uri, $query, $headers);
        } catch (RedditApiException $e) {
            if ($e->getStatusCode() === 401 && $this->token->refreshToken) {
                $this->logger?->warning('Access token expired, attempting refresh');
                $this->token = $this->refresher->refresh($this->token);
                $this->client->withToken($this->token->accessToken);
                return $this->client->request($method, $uri, $query, $headers);
            }
            throw $e;
        }
    }

    public function me(): \Avansaber\RedditApi\Resources\Me
    {
        return $this->client->me();
    }
}

