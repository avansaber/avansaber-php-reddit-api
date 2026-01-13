<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Data;

/**
 * Represents a Reddit user account (t2 thing).
 */
final class User
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly float $createdUtc,
        public readonly int $linkKarma,
        public readonly int $commentKarma,
        public readonly int $totalKarma,
        public readonly bool $isEmployee,
        public readonly bool $isMod,
        public readonly bool $isGold,
        public readonly bool $verified,
        public readonly bool $hasVerifiedEmail,
        public readonly ?string $iconImg,
        public readonly bool $over18,
        public readonly bool $isSuspended,
    ) {
    }
}
