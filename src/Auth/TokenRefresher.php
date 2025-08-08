<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Auth;

use Avansaber\RedditApi\Config\Config;
use Psr\Log\LoggerInterface;

final class TokenRefresher
{
    public function __construct(
        private readonly Auth $auth,
        private readonly TokenStorageInterface $storage,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Refreshes a token if expired or on demand, persists it, and returns the updated Token.
     */
    public function refresh(Token $token): Token
    {
        $data = $this->auth->refreshAccessToken($this->clientId, $this->clientSecret, $token->refreshToken ?? '');

        $newAccessToken = (string) ($data['access_token'] ?? '');
        $newRefreshToken = isset($data['refresh_token']) && is_string($data['refresh_token'])
            ? $data['refresh_token']
            : $token->refreshToken;
        $expiresIn = (int) ($data['expires_in'] ?? 3600);
        $expiresAt = time() + $expiresIn;

        $updated = new Token(
            providerUserId: $token->providerUserId,
            accessToken: $newAccessToken,
            refreshToken: $newRefreshToken,
            expiresAtEpoch: $expiresAt,
            scopes: $token->scopes,
            ownerUserId: $token->ownerUserId,
            ownerTenantId: $token->ownerTenantId,
        );

        $this->storage->save($updated);
        $this->logger?->info('Refreshed Reddit access token', [
            'provider_user_id' => $token->providerUserId,
            'owner_user_id' => $token->ownerUserId,
            'owner_tenant_id' => $token->ownerTenantId,
        ]);

        return $updated;
    }
}

