<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Data;

final class Subreddit
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $title,
        public readonly string $publicDescription,
        public readonly int $subscribers,
        public readonly bool $over18,
        public readonly string $url,
    ) {
    }
}

