<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Value;

/**
 * Value object for Reddit subreddit names.
 *
 * Subreddit name rules:
 * - 3-21 characters
 * - Alphanumeric characters and underscores only
 * - Cannot start with an underscore
 * - Does not include the r/ prefix
 */
final class SubredditName
{
    private const MIN_LENGTH = 3;
    private const MAX_LENGTH = 21;
    private const PATTERN = '/^[A-Za-z0-9][A-Za-z0-9_]*$/';

    private readonly string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);

        // Strip r/ prefix if provided
        if (str_starts_with(strtolower($trimmed), 'r/')) {
            $trimmed = substr($trimmed, 2);
        }

        if ($trimmed === '') {
            throw new \InvalidArgumentException('Subreddit name cannot be empty');
        }

        $length = strlen($trimmed);
        if ($length < self::MIN_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Subreddit name must be at least %d characters, got %d', self::MIN_LENGTH, $length)
            );
        }

        if ($length > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Subreddit name cannot exceed %d characters, got %d', self::MAX_LENGTH, $length)
            );
        }

        if (!preg_match(self::PATTERN, $trimmed)) {
            throw new \InvalidArgumentException(
                'Subreddit name must contain only alphanumeric characters and underscores, and cannot start with an underscore'
            );
        }

        $this->value = $trimmed;
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
