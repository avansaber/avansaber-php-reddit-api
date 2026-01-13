<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Data;

/**
 * Represents a flair template option.
 */
final class Flair
{
    public function __construct(
        public readonly string $id,
        public readonly string $text,
        public readonly ?string $textColor,
        public readonly ?string $backgroundColor,
        public readonly bool $textEditable,
        public readonly string $type,
        public readonly ?string $cssClass,
    ) {
    }
}
