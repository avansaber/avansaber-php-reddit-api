<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Data;

/**
 * Minimal listing container with after/before cursors and items payload.
 *
 * @template T
 */
final class Listing
{
    /**
     * @param list<T> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly ?string $after,
        public readonly ?string $before,
    ) {
    }
}

