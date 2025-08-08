<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Auth;

interface TokenStorageInterface
{
    public function save(Token $token): void;

    /**
     * @return Token|null
     */
    public function findByOwnerAndProviderUserId(?string $ownerUserId, ?string $ownerTenantId, string $providerUserId): ?Token;

    /**
     * @return list<Token>
     */
    public function allForOwner(?string $ownerUserId, ?string $ownerTenantId): array;

    public function deleteByOwnerAndProviderUserId(?string $ownerUserId, ?string $ownerTenantId, string $providerUserId): void;
}

