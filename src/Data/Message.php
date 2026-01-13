<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Data;

/**
 * Represents a Reddit private message.
 */
final class Message
{
    public function __construct(
        public readonly string $id,
        public readonly string $fullname,
        public readonly string $author,
        public readonly string $subject,
        public readonly string $body,
        public readonly string $bodyHtml,
        public readonly float $createdUtc,
        public readonly ?string $dest,
        public readonly ?string $parentId,
        public readonly bool $isNew,
        public readonly bool $wasComment,
        public readonly ?string $context,
        public readonly ?string $subreddit,
    ) {
    }
}
