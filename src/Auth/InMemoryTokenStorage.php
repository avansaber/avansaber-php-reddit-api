<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Auth;

final class InMemoryTokenStorage implements TokenStorageInterface
{
    /** @var array<string, Token> */
    private array $tokens = [];

    public function save(Token $token): void
    {
        $key = $this->key($token->ownerUserId, $token->ownerTenantId, $token->providerUserId);
        $this->tokens[$key] = $token;
    }

    public function findByOwnerAndProviderUserId(?string $ownerUserId, ?string $ownerTenantId, string $providerUserId): ?Token
    {
        $key = $this->key($ownerUserId, $ownerTenantId, $providerUserId);
        return $this->tokens[$key] ?? null;
    }

    public function allForOwner(?string $ownerUserId, ?string $ownerTenantId): array
    {
        $result = [];
        foreach ($this->tokens as $key => $token) {
            if ($token->ownerUserId === $ownerUserId && $token->ownerTenantId === $ownerTenantId) {
                $result[] = $token;
            }
        }
        return $result;
    }

    public function deleteByOwnerAndProviderUserId(?string $ownerUserId, ?string $ownerTenantId, string $providerUserId): void
    {
        $key = $this->key($ownerUserId, $ownerTenantId, $providerUserId);
        unset($this->tokens[$key]);
    }

    private function key(?string $ownerUserId, ?string $ownerTenantId, string $providerUserId): string
    {
        return ($ownerUserId ?? '-') . '|' . ($ownerTenantId ?? '-') . '|' . $providerUserId;
    }
}

