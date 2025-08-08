<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Http;

use Avansaber\RedditApi\Config\Config;
use Avansaber\RedditApi\Exceptions\RedditApiException;
use Avansaber\RedditApi\Resources\Me;
use Avansaber\RedditApi\Resources\Search;
use Avansaber\RedditApi\Resources\Subreddit;
use Avansaber\RedditApi\Resources\User;
use Avansaber\RedditApi\Resources\PrivateMessages;
use Avansaber\RedditApi\Resources\Moderation;
use Avansaber\RedditApi\Resources\Flair;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

final class RedditApiClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly Config $config,
        private readonly ?LoggerInterface $logger = null,
        private string $accessToken = '',
        private ?SleeperInterface $sleeper = null,
    ) {
        $this->sleeper = $this->sleeper ?? new NoopSleeper();
    }

    public function withToken(string $accessToken): self
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Sends an HTTP request to Reddit API using PSR-18 client.
     *
     * @param array<string, string|int|bool> $query
     * @param array<string, string> $headers
     */
    public function request(string $method, string $uri, array $query = [], array $headers = [], ?array $form = null): string
    {
        $url = $this->config->getBaseUri() . '/' . ltrim($uri, '/');
        if (!empty($query)) {
            $qs = http_build_query($query);
            $url .= (str_contains($url, '?') ? '&' : '?') . $qs;
        }

        $request = $this->requestFactory->createRequest($method, $url)
            ->withHeader('User-Agent', $this->config->getUserAgent())
            ->withHeader('Accept', 'application/json');

        if ($this->accessToken !== '') {
            $request = $request->withHeader('Authorization', 'Bearer ' . $this->accessToken);
        }

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, (string) $value);
        }

        if ($form !== null) {
            $body = http_build_query($form);
            $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');
            $request = $request->withBody($this->streamFactory->createStream($body));
        }

        $attempt = 0;
        $maxAttempts = max(1, $this->config->getMaxRetries() + 1);
        $lastException = null;
        while ($attempt < $maxAttempts) {
            $attempt++;
            try {
                $response = $this->httpClient->sendRequest($request);
            } catch (\Throwable $e) {
                $lastException = new RedditApiException('Transport error: ' . $e->getMessage(), 0, null, $e);
                $this->backoff($attempt, null);
                continue;
            }

            $status = $response->getStatusCode();
            $body = (string) $response->getBody();

            if ($status >= 200 && $status < 300) {
                // Optionally parse rate limit headers here (not yet surfaced)
                return $body;
            }

            if ($status === 429) {
                $retryAfter = $response->getHeaderLine('Retry-After');
                $this->backoff($attempt, $retryAfter !== '' ? (int) $retryAfter : null);
                continue;
            }

            if ($status >= 500 && $status < 600) {
                $this->backoff($attempt, null);
                continue;
            }

            throw new RedditApiException('HTTP ' . $status . ' from Reddit API', $status, $body);
        }

        if ($lastException instanceof RedditApiException) {
            throw $lastException;
        }
        throw new RedditApiException('Request failed after retries');
    }

    private function backoff(int $attempt, ?int $retryAfterSeconds): void
    {
        if ($attempt >= ($this->config->getMaxRetries() + 1)) {
            return;
        }
        $delayMs = $retryAfterSeconds !== null
            ? max(0, $retryAfterSeconds * 1000)
            : (int) (100.0 * (2 ** ($attempt - 1))); // 100ms, 200ms, 400ms ...
        $this->sleeper?->sleep($delayMs);
    }

    public function me(): Me
    {
        return new Me($this);
    }

    public function search(): Search
    {
        return new Search($this);
    }

    public function subreddit(): Subreddit
    {
        return new Subreddit($this);
    }

    public function user(): User
    {
        return new User($this);
    }

    public function links(): \Avansaber\RedditApi\Resources\Links
    {
        return new \Avansaber\RedditApi\Resources\Links($this);
    }

    public function messages(): PrivateMessages
    {
        return new PrivateMessages($this);
    }

    public function moderation(): Moderation
    {
        return new Moderation($this);
    }

    public function flair(): Flair
    {
        return new Flair($this);
    }
}