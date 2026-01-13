<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Config;

final class Config
{
    private string $baseUri;
    private string $authBaseUri;
    private string $userAgent;
    private float $timeoutSeconds;
    private int $maxRetries;

    /**
     * @param string $userAgent Reddit-compliant User-Agent (required)
     * @param string $baseUri Base URI for API calls (default: https://oauth.reddit.com)
     * @param float $timeoutSeconds HTTP request timeout
     * @param int $maxRetries Maximum retry attempts for 429/5xx errors
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