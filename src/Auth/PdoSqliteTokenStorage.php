<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Auth;

use PDO;

final class PdoSqliteTokenStorage implements TokenStorageInterface
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $table = 'reddit_tokens',
        bool $autoCreateTable = true,
    ) {
        if ($autoCreateTable) {
            $this->createTableIfNotExists();
        }
    }

    public function save(Token $token): void
    {
        // Delete existing then insert to avoid requiring UNIQUE constraints
        $this->deleteByOwnerAndProviderUserId($token->ownerUserId, $token->ownerTenantId, $token->providerUserId);

        $sql = sprintf(
            'INSERT INTO %s (provider_user_id, access_token, refresh_token, expires_at_epoch, scopes, owner_user_id, owner_tenant_id) VALUES (:pid, :at, :rt, :exp, :sc, :ouid, :otid)',
            $this->table
        );
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':pid' => $token->providerUserId,
            ':at' => $token->accessToken,
            ':rt' => $token->refreshToken,
            ':exp' => $token->expiresAtEpoch,
            ':sc' => json_encode(array_values($token->scopes), JSON_THROW_ON_ERROR),
            ':ouid' => $token->ownerUserId,
            ':otid' => $token->ownerTenantId,
        ]);
    }

    public function findByOwnerAndProviderUserId(?string $ownerUserId, ?string $ownerTenantId, string $providerUserId): ?Token
    {
        $where = ['provider_user_id = :pid'];
        $params = [':pid' => $providerUserId];

        if ($ownerUserId === null) {
            $where[] = 'owner_user_id IS NULL';
        } else {
            $where[] = 'owner_user_id = :ouid';
            $params[':ouid'] = $ownerUserId;
        }

        if ($ownerTenantId === null) {
            $where[] = 'owner_tenant_id IS NULL';
        } else {
            $where[] = 'owner_tenant_id = :otid';
            $params[':otid'] = $ownerTenantId;
        }

        $sql = sprintf(
            'SELECT provider_user_id, access_token, refresh_token, expires_at_epoch, scopes, owner_user_id, owner_tenant_id FROM %s WHERE %s LIMIT 1',
            $this->table,
            implode(' AND ', $where)
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }

        $scopes = [];
        if (isset($row['scopes']) && is_string($row['scopes']) && $row['scopes'] !== '') {
            try {
                $decoded = json_decode($row['scopes'], true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    $scopes = array_values(array_map('strval', $decoded));
                }
            } catch (\Throwable) {
                $scopes = [];
            }
        }

        return new Token(
            providerUserId: (string) $row['provider_user_id'],
            accessToken: (string) $row['access_token'],
            refreshToken: $row['refresh_token'] !== null ? (string) $row['refresh_token'] : null,
            expiresAtEpoch: (int) $row['expires_at_epoch'],
            scopes: $scopes,
            ownerUserId: $row['owner_user_id'] !== null ? (string) $row['owner_user_id'] : null,
            ownerTenantId: $row['owner_tenant_id'] !== null ? (string) $row['owner_tenant_id'] : null,
        );
    }

    public function allForOwner(?string $ownerUserId, ?string $ownerTenantId): array
    {
        $sql = sprintf(
            'SELECT provider_user_id, access_token, refresh_token, expires_at_epoch, scopes, owner_user_id, owner_tenant_id FROM %s WHERE owner_user_id %s AND owner_tenant_id %s',
            $this->table,
            $ownerUserId === null ? 'IS NULL' : '= :ouid',
            $ownerTenantId === null ? 'IS NULL' : '= :otid'
        );
        $stmt = $this->pdo->prepare($sql);
        $params = [];
        if ($ownerUserId !== null) {
            $params[':ouid'] = $ownerUserId;
        }
        if ($ownerTenantId !== null) {
            $params[':otid'] = $ownerTenantId;
        }
        $stmt->execute($params);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $tokens = [];
        foreach ($rows as $row) {
            $scopes = [];
            if (isset($row['scopes']) && is_string($row['scopes']) && $row['scopes'] !== '') {
                try {
                    $decoded = json_decode($row['scopes'], true, 512, JSON_THROW_ON_ERROR);
                    if (is_array($decoded)) {
                        $scopes = array_values(array_map('strval', $decoded));
                    }
                } catch (\Throwable) {
                    $scopes = [];
                }
            }

            $tokens[] = new Token(
                providerUserId: (string) $row['provider_user_id'],
                accessToken: (string) $row['access_token'],
                refreshToken: $row['refresh_token'] !== null ? (string) $row['refresh_token'] : null,
                expiresAtEpoch: (int) $row['expires_at_epoch'],
                scopes: $scopes,
                ownerUserId: $row['owner_user_id'] !== null ? (string) $row['owner_user_id'] : null,
                ownerTenantId: $row['owner_tenant_id'] !== null ? (string) $row['owner_tenant_id'] : null,
            );
        }

        return $tokens;
    }

    public function deleteByOwnerAndProviderUserId(?string $ownerUserId, ?string $ownerTenantId, string $providerUserId): void
    {
        $sql = sprintf(
            'DELETE FROM %s WHERE provider_user_id = :pid AND owner_user_id %s AND owner_tenant_id %s',
            $this->table,
            $ownerUserId === null ? 'IS NULL' : '= :ouid',
            $ownerTenantId === null ? 'IS NULL' : '= :otid'
        );
        $stmt = $this->pdo->prepare($sql);
        $params = [':pid' => $providerUserId];
        if ($ownerUserId !== null) {
            $params[':ouid'] = $ownerUserId;
        }
        if ($ownerTenantId !== null) {
            $params[':otid'] = $ownerTenantId;
        }
        $stmt->execute($params);
    }

    private function createTableIfNotExists(): void
    {
        $sql = sprintf('CREATE TABLE IF NOT EXISTS %s (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            provider_user_id TEXT NOT NULL,
            access_token TEXT NOT NULL,
            refresh_token TEXT NULL,
            expires_at_epoch INTEGER NOT NULL,
            scopes TEXT NULL,
            owner_user_id TEXT NULL,
            owner_tenant_id TEXT NULL
        )', $this->table);

        $this->pdo->exec($sql);
        // Optional indexes to speed up lookups
        $this->pdo->exec(sprintf('CREATE INDEX IF NOT EXISTS idx_%s_owner ON %s (owner_user_id, owner_tenant_id)', $this->table, $this->table));
        $this->pdo->exec(sprintf('CREATE INDEX IF NOT EXISTS idx_%s_provider ON %s (provider_user_id)', $this->table, $this->table));
    }
}

