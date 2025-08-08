<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Tests\Unit;

use Avansaber\RedditApi\Auth\PdoSqliteTokenStorage;
use Avansaber\RedditApi\Auth\Token;
use PDO;
use PHPUnit\Framework\TestCase;

final class PdoSqliteTokenStorageTest extends TestCase
{
    public function test_save_and_find_and_delete(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $storage = new PdoSqliteTokenStorage($pdo);

        $token = new Token(
            providerUserId: 'u_123',
            accessToken: 'at-1',
            refreshToken: 'rt-1',
            expiresAtEpoch: time() + 3600,
            scopes: ['read', 'identity'],
            ownerUserId: '42',
            ownerTenantId: null,
        );

        $this->assertNull($storage->findByOwnerAndProviderUserId('42', null, 'u_123'));

        $storage->save($token);

        $found = $storage->findByOwnerAndProviderUserId('42', null, 'u_123');
        $this->assertNotNull($found);
        $this->assertSame('at-1', $found->accessToken);
        $this->assertSame(['read', 'identity'], $found->scopes);

        $all = $storage->allForOwner('42', null);
        $this->assertCount(1, $all);

        // update token
        $updated = new Token(
            providerUserId: 'u_123',
            accessToken: 'at-2',
            refreshToken: 'rt-2',
            expiresAtEpoch: time() + 7200,
            scopes: ['read'],
            ownerUserId: '42',
            ownerTenantId: null,
        );
        $storage->save($updated);
        $found2 = $storage->findByOwnerAndProviderUserId('42', null, 'u_123');
        $this->assertSame('at-2', $found2->accessToken);
        $this->assertSame(['read'], $found2->scopes);

        $storage->deleteByOwnerAndProviderUserId('42', null, 'u_123');
        $this->assertNull($storage->findByOwnerAndProviderUserId('42', null, 'u_123'));
    }
}

