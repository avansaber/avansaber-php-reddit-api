<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Http;

/**
 * Production sleeper that actually pauses execution.
 * Used for rate limit backoff in production environments.
 */
final class RealSleeper implements SleeperInterface
{
    public function sleep(int $milliseconds): void
    {
        if ($milliseconds > 0) {
            usleep($milliseconds * 1000);
        }
    }
}
