<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Http;

final class NoopSleeper implements SleeperInterface
{
    public function sleep(int $milliseconds): void
    {
        // no-op for tests
    }
}

