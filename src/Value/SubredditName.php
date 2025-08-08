<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Value;

final class SubredditName
{
    public function __construct(private readonly string $value)
    {
        if ($value === '' || preg_match('/\s/', $value)) {
            throw new \InvalidArgumentException('Invalid subreddit name: ' . $value);
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


