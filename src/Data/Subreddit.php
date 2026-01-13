<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Data;

/**
 * Represents a Reddit subreddit (t5 thing).
 */
final class Subreddit
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $displayName,
        public readonly string $title,
        public readonly string $publicDescription,
        public readonly string $description,
        public readonly int $subscribers,
        public readonly int $activeUserCount,
        public readonly float $createdUtc,
        public readonly bool $over18,
        public readonly string $url,
        public readonly string $subredditType,
        public readonly bool $quarantine,
        public readonly ?string $bannerImg,
        public readonly ?string $iconImg,
        public readonly ?string $headerImg,
        public readonly ?string $primaryColor,
        public readonly ?string $keyColor,
    ) {
    }
}
