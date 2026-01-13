<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Data;

/**
 * Represents karma breakdown for a single subreddit.
 */
final class KarmaBreakdown
{
    public function __construct(
        public readonly string $subreddit,
        public readonly int $linkKarma,
        public readonly int $commentKarma,
    ) {
    }
}
