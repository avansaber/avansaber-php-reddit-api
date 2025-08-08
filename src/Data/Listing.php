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

    /**
     * Simple generator to iterate pages using a provided page fetcher.
     *
     * @param callable(?string $after): Listing<T> $fetchNext
     * @return \Generator<int, T, void, void>
     */
    public function iterate(callable $fetchNext): \Generator
    {
        $current = $this;
        while (true) {
            foreach ($current->items as $item) {
                yield $item;
            }
            if ($current->after === null) {
                break;
            }
            $current = $fetchNext($current->after);
        }
    }
}

