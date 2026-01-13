<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Value;

/**
 * Value object for Reddit usernames.
 *
 * Reddit username rules:
 * - 3-20 characters
 * - Alphanumeric characters, hyphens, and underscores only
 * - Cannot start with a hyphen or underscore
 */
final class Username
{
    private const MIN_LENGTH = 3;
    private const MAX_LENGTH = 20;
    private const PATTERN = '/^[A-Za-z0-9][A-Za-z0-9_-]*$/';

    public function __construct(private readonly string $value)
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new \InvalidArgumentException('Username cannot be empty');
        }

        $length = strlen($trimmed);
        if ($length < self::MIN_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Username must be at least %d characters, got %d', self::MIN_LENGTH, $length)
            );
        }

        if ($length > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Username cannot exceed %d characters, got %d', self::MAX_LENGTH, $length)
            );
        }

        if (!preg_match(self::PATTERN, $trimmed)) {
            throw new \InvalidArgumentException(
                'Username must contain only alphanumeric characters, hyphens, and underscores, and cannot start with a hyphen or underscore'
            );
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
