<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Config;

final class Config
{
    private const MIN_TIMEOUT_SECONDS = 1.0;
    private const MAX_TIMEOUT_SECONDS = 120.0;
    private const MIN_RETRIES = 0;
    private const MAX_RETRIES = 10;

    private string $baseUri;
    private string $authBaseUri;
    private string $userAgent;
    private float $timeoutSeconds;
    private int $maxRetries;

    /**
     * @param string $userAgent Reddit-compliant User-Agent (required). Format: <platform>:<app ID>:<version> (by /u/<username>)
     * @param string $baseUri Base URI for API calls (default: https://oauth.reddit.com)
     * @param float $timeoutSeconds HTTP request timeout (1-120 seconds)
     * @param int $maxRetries Maximum retry attempts for 429/5xx errors (0-10)
     * @param string $authBaseUri Base URI for OAuth endpoints (default: https://www.reddit.com)
     */
    public function __construct(
        string $userAgent,
        string $baseUri = 'https://oauth.reddit.com',
        float $timeoutSeconds = 10.0,
        int $maxRetries = 3,
        string $authBaseUri = 'https://www.reddit.com'
    ) {
        $userAgent = trim($userAgent);
        if ($userAgent === '') {
            throw new \InvalidArgumentException('User-Agent must not be empty. Reddit requires a descriptive UA.');
        }

        // Validate timeout is within reasonable bounds
        if ($timeoutSeconds < self::MIN_TIMEOUT_SECONDS) {
            throw new \InvalidArgumentException(
                sprintf('Timeout must be at least %.1f seconds, got %.3f', self::MIN_TIMEOUT_SECONDS, $timeoutSeconds)
            );
        }
        if ($timeoutSeconds > self::MAX_TIMEOUT_SECONDS) {
            throw new \InvalidArgumentException(
                sprintf('Timeout cannot exceed %.1f seconds, got %.1f', self::MAX_TIMEOUT_SECONDS, $timeoutSeconds)
            );
        }

        // Validate retries are within reasonable bounds
        if ($maxRetries < self::MIN_RETRIES) {
            throw new \InvalidArgumentException(
                sprintf('Max retries cannot be negative, got %d', $maxRetries)
            );
        }
        if ($maxRetries > self::MAX_RETRIES) {
            throw new \InvalidArgumentException(
                sprintf('Max retries cannot exceed %d, got %d', self::MAX_RETRIES, $maxRetries)
            );
        }

        $this->userAgent = $userAgent;
        $this->baseUri = rtrim($baseUri, '/');
        $this->authBaseUri = rtrim($authBaseUri, '/');
        $this->timeoutSeconds = $timeoutSeconds;
        $this->maxRetries = $maxRetries;
    }

    public function getBaseUri(): string
    {
        return $this->baseUri;
    }

    public function getAuthBaseUri(): string
    {
        return $this->authBaseUri;
    }

    public function getAuthorizeUrl(): string
    {
        return $this->authBaseUri . '/api/v1/authorize';
    }

    public function getAccessTokenUrl(): string
    {
        return $this->authBaseUri . '/api/v1/access_token';
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function getTimeoutSeconds(): float
    {
        return $this->timeoutSeconds;
    }

    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }
}
