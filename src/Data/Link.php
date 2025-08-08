<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Data;

final class Link
{
    public function __construct(
        public readonly string $id,
        public readonly string $fullname,
        public readonly string $title,
        public readonly string $author,
        public readonly string $subreddit,
        public readonly string $permalink,
        public readonly string $url,
        public readonly int $score,
    ) {
    }
}

