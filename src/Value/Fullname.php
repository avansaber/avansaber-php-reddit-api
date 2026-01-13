<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Value;

/**
 * Represents a Reddit fullname (thing ID).
 *
 * Fullnames follow the format: t{type}_{base36id}
 * Types:
 *   t1 = comment
 *   t2 = account
 *   t3 = link/post
 *   t4 = private message
 *   t5 = subreddit
 *   t6 = award
 *   t7 = modqueue item (internal)
 *   t8 = modmail (internal)
 */
final class Fullname
{
    public function __construct(private readonly string $value)
    {
        // Allow t1-t8 types and alphanumeric IDs (base36)
        if ($value === '' || !preg_match('/^t[1-8]_[A-Za-z0-9]+$/', $value)) {
            throw new \InvalidArgumentException('Invalid Reddit fullname: ' . $value);
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}


