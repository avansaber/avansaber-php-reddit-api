<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Tests\Unit;

use Avansaber\RedditApi\Auth\InMemoryTokenStorage;
use Avansaber\RedditApi\Auth\Token;
use PHPUnit\Framework\TestCase;

final class InMemoryTokenStorageTest extends TestCase
{
    public function test_save_find_all_delete_flow(): void
    {
        $storage = new InMemoryTokenStorage();

        $this->assertNull($storage->findByOwnerAndProviderUserId('u1', null, 'p123'));

        $t1 = new Token(
            providerUserId: 'p123',
            accessToken: 'at1',
            refreshToken: 'rt1',
            expiresAtEpoch: time() + 1000,
            scopes: ['read'],
            ownerUserId: 'u1',
            ownerTenantId: null,
        );
        $storage->save($t1);

        $found = $storage->findByOwnerAndProviderUserId('u1', null, 'p123');
        $this->assertNotNull($found);
        $this->assertSame('at1', $found->accessToken);

        $all = $storage->allForOwner('u1', null);
        $this->assertCount(1, $all);

        // Update
        $t2 = new Token(
            providerUserId: 'p123',
            accessToken: 'at2',
            refreshToken: 'rt2',
            expiresAtEpoch: time() + 2000,
            scopes: ['read', 'identity'],
            ownerUserId: 'u1',
            ownerTenantId: null,
        );
        $storage->save($t2);
        $found2 = $storage->findByOwnerAndProviderUserId('u1', null, 'p123');
        $this->assertSame('at2', $found2->accessToken);
        $this->assertSame(['read', 'identity'], $found2->scopes);

        $storage->deleteByOwnerAndProviderUserId('u1', null, 'p123');
        $this->assertNull($storage->findByOwnerAndProviderUserId('u1', null, 'p123'));
    }
}