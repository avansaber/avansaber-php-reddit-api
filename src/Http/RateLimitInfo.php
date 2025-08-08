<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Http;

final class RateLimitInfo
{
    public function __construct(
        public readonly ?float $remaining,
        public readonly ?float $used,
        public readonly ?float $resetSeconds,
    ) {
    }
}

