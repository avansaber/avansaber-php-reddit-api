<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Value;

final class Username
{
    public function __construct(private readonly string $value)
    {
        if ($value === '' || preg_match('/\s/', $value)) {
            throw new \InvalidArgumentException('Invalid username: ' . $value);
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


