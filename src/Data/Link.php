<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Data;

/**
 * Represents a Reddit post/link (t3 thing).
 */
final class Link
{
    public function __construct(
        public readonly string $id,
        public readonly string $fullname,
        public readonly string $title,
        public readonly string $author,
        public readonly string $subreddit,
        public readonly string $subredditId,
        public readonly string $permalink,
        public readonly string $url,
        public readonly int $score,
        public readonly int $ups,
        public readonly int $downs,
        public readonly int $numComments,
        public readonly float $createdUtc,
        public readonly bool $isSelf,
        public readonly string $selftext,
        public readonly bool $over18,
        public readonly bool $spoiler,
        public readonly bool $locked,
        public readonly bool $stickied,
        public readonly bool $archived,
        public readonly ?string $linkFlairText,
        public readonly ?string $authorFlairText,
        public readonly bool|float $edited,
        public readonly ?string $thumbnail,
        public readonly ?string $domain,
    ) {
    }
}
