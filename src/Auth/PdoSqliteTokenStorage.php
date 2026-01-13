<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Auth;

use PDO;

final class PdoSqliteTokenStorage implements TokenStorageInterface
{
    /**
     * @param PDO $pdo PDO instance (SQLite)
     * @param string $table Table name for storing tokens
     * @param bool $autoCreateTable Auto-create table if it doesn't exist
     * @param string|null $encryptionKey 32-byte key for sodium encryption (null = no encryption)
     */
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $table = 'reddit_tokens',
        bool $autoCreateTable = true,
        private readonly ?string $encryptionKey = null,
    ) {
        if ($encryptionKey !== null && strlen($encryptionKey) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new \InvalidArgumentException(
                sprintf('Encryption key must be exactly %d bytes', SODIUM_CRYPTO_SECRETBOX_KEYBYTES)
            );
        }
        if ($autoCreateTable) {
            $this->createTableIfNotExists();
        }
    }

    public function save(Token $token): void
    {
        // Use ON CONFLICT DO UPDATE (proper UPSERT) to avoid race conditions
        // We use COALESCE to handle NULL values in the unique constraint match
        $sql = sprintf(
            'INSERT INTO %s (provider_user_id, access_token, refresh_token, expires_at_epoch, scopes, owner_user_id, owner_tenant_id)
             VALUES (:pid, :at, :rt, :exp, :sc, :ouid, :otid)
             ON CONFLICT(provider_user_id, COALESCE(owner_user_id, \'\'), COALESCE(owner_tenant_id, \'\'))
             DO UPDATE SET access_token = excluded.access_token, refresh_token = excluded.refresh_token, expires_at_epoch = excluded.expires_at_epoch, scopes = excluded.scopes',
            $this->table
        );
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':pid' => $token->providerUserId,
            ':at' => $this->encrypt($token->accessToken),
            ':rt' => $token->refreshToken !== null ? $this->encrypt($token->refreshToken) : null,
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

        return $this->rowToToken($row);
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
            $tokens[] = $this->rowToToken($row);
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

    /**
     * Delete all tokens that have expired.
     *
     * @return int Number of tokens deleted
     */
    public function deleteExpiredTokens(): int
    {
        $sql = sprintf('DELETE FROM %s WHERE expires_at_epoch < :now', $this->table);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':now' => time()]);
        return $stmt->rowCount();
    }

    /**
     * Generate a new encryption key suitable for use with this storage.
     *
     * @return string 32-byte binary string
     */
    public static function generateEncryptionKey(): string
    {
        return sodium_crypto_secretbox_keygen();
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
        // Unique index using COALESCE to handle NULL values correctly
        $this->pdo->exec(sprintf(
            'CREATE UNIQUE INDEX IF NOT EXISTS idx_%s_unique_token ON %s (provider_user_id, COALESCE(owner_user_id, \'\'), COALESCE(owner_tenant_id, \'\'))',
            $this->table,
            $this->table
        ));
        // Optional indexes to speed up lookups
        $this->pdo->exec(sprintf('CREATE INDEX IF NOT EXISTS idx_%s_owner ON %s (owner_user_id, owner_tenant_id)', $this->table, $this->table));
        $this->pdo->exec(sprintf('CREATE INDEX IF NOT EXISTS idx_%s_provider ON %s (provider_user_id)', $this->table, $this->table));
        $this->pdo->exec(sprintf('CREATE INDEX IF NOT EXISTS idx_%s_expires ON %s (expires_at_epoch)', $this->table, $this->table));
    }

    /**
     * @param array<string, mixed> $row
     */
    private function rowToToken(array $row): Token
    {
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

        $accessToken = (string) $row['access_token'];
        $refreshToken = $row['refresh_token'] !== null ? (string) $row['refresh_token'] : null;

        return new Token(
            providerUserId: (string) $row['provider_user_id'],
            accessToken: $this->decrypt($accessToken),
            refreshToken: $refreshToken !== null ? $this->decrypt($refreshToken) : null,
            expiresAtEpoch: (int) $row['expires_at_epoch'],
            scopes: $scopes,
            ownerUserId: $row['owner_user_id'] !== null ? (string) $row['owner_user_id'] : null,
            ownerTenantId: $row['owner_tenant_id'] !== null ? (string) $row['owner_tenant_id'] : null,
        );
    }

    private function encrypt(string $data): string
    {
        if ($this->encryptionKey === null) {
            return $data;
        }

        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox($data, $nonce, $this->encryptionKey);

        // Prefix ciphertext with nonce for storage, then base64 encode
        return base64_encode($nonce . $ciphertext);
    }

    private function decrypt(string $data): string
    {
        if ($this->encryptionKey === null) {
            return $data;
        }

        $decoded = base64_decode($data, true);
        if ($decoded === false) {
            // Data is not base64 encoded, likely unencrypted legacy data
            return $data;
        }

        if (strlen($decoded) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES) {
            // Too short to be encrypted, likely unencrypted legacy data
            return $data;
        }

        $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, $this->encryptionKey);
        if ($plaintext === false) {
            // Decryption failed, likely unencrypted legacy data or wrong key
            return $data;
        }

        return $plaintext;
    }
}
