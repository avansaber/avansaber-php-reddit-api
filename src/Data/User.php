<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Data;

final class User
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly bool $isEmployee,
        public readonly bool $isMod,
        public readonly float $createdUtc,
    ) {
    }
}