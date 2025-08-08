<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Auth;

final class Token
{
    /**
     * @param array<int, string> $scopes
     */
    public function __construct(
        public readonly string $providerUserId,
        public readonly string $accessToken,
        public readonly ?string $refreshToken,
        public readonly int $expiresAtEpoch,
        public readonly array $scopes = [],
        public readonly ?string $ownerUserId = null,
        public readonly ?string $ownerTenantId = null,
    ) {
    }
}

