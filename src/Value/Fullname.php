<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Value;

final class Fullname
{
    public function __construct(private readonly string $value)
    {
        if ($value === '' || !preg_match('/^t[1-6]_[A-Za-z0-9]+$/', $value)) {
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


