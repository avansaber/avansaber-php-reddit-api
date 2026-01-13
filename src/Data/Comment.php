<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Data;

/**
 * Represents a Reddit comment (t1 thing).
 */
final class Comment
{
    public function __construct(
        public readonly string $id,
        public readonly string $fullname,
        public readonly string $author,
        public readonly string $body,
        public readonly string $bodyHtml,
        public readonly string $permalink,
        public readonly int $score,
        public readonly int $ups,
        public readonly int $downs,
        public readonly float $createdUtc,
        public readonly string $subreddit,
        public readonly string $subredditId,
        public readonly string $parentId,
        public readonly string $linkId,
        public readonly bool $isSubmitter,
        public readonly bool $stickied,
        public readonly bool $scoreHidden,
        public readonly bool $locked,
        public readonly bool|float $edited,
        public readonly ?string $authorFlairText,
    ) {
    }
}
