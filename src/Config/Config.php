<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Config;

final class Config
{
    private string $baseUri;
    private string $userAgent;
    private float $timeoutSeconds;
    private int $maxRetries;

    public function __construct(
        string $userAgent,
        string $baseUri = 'https://oauth.reddit.com',
        float $timeoutSeconds = 10.0,
        int $maxRetries = 3
    ) {
        $userAgent = trim($userAgent);
        if ($userAgent === '') {
            throw new \InvalidArgumentException('User-Agent must not be empty. Reddit requires a descriptive UA.');
        }

        $this->userAgent = $userAgent;
        $this->baseUri = rtrim($baseUri, '/');
        $this->timeoutSeconds = $timeoutSeconds;
        $this->maxRetries = $maxRetries;
    }

    public function getBaseUri(): string
    {
        return $this->baseUri;
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