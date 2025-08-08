<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Data;

final class Comment
{
    public function __construct(
        public readonly string $id,
        public readonly string $fullname,
        public readonly string $author,
        public readonly string $body,
        public readonly string $permalink,
        public readonly int $score,
    ) {
    }
}

