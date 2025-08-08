<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Tests\Unit;

use Avansaber\RedditApi\Auth\Auth;
use Avansaber\RedditApi\Auth\InMemoryTokenStorage;
use Avansaber\RedditApi\Auth\Token;
use Avansaber\RedditApi\Auth\TokenRefresher;
use Avansaber\RedditApi\Config\Config;
use Avansaber\RedditApi\Exceptions\RedditApiException;
use Avansaber\RedditApi\Http\AutoRefreshingClient;
use Avansaber\RedditApi\Http\RedditApiClient;
use Http\Mock\Client as MockHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class AutoRefreshingClientTest extends TestCase
{
    public function test_refresh_on_401_then_retry_success(): void
    {
        $httpClient = new MockHttpClient();
        $psr17 = new Psr17Factory();
        $config = new Config('ua/1.0; contact admin@example.com');

        // Set up the core client and auth
        $coreClient = new RedditApiClient($httpClient, $psr17, $psr17, $config, new NullLogger());
        $auth = new Auth($httpClient, $psr17, $psr17, $config, new NullLogger());
        $storage = new InMemoryTokenStorage();

        // Initial token
        $token = new Token(
            providerUserId: 'u_1',
            accessToken: 'expired',
            refreshToken: 'refresh-1',
            expiresAtEpoch: time() - 10,
            scopes: ['read'],
            ownerUserId: '42',
            ownerTenantId: null,
        );
        $storage->save($token);

        $refresher = new TokenRefresher($auth, $storage, 'client', 'secret', new NullLogger());
        $client = new AutoRefreshingClient($coreClient, $refresher, $token, new NullLogger());

        // First call fails with 401, then token refresh call returns new token, then retry succeeds
        $httpClient->addResponse(new Response(401, [], 'unauthorized'));
        $httpClient->addResponse(new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'access_token' => 'new-token',
            'expires_in' => 3600,
        ], JSON_THROW_ON_ERROR)));
        $httpClient->addResponse(new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'id' => 'me',
            'name' => 'johndoe'
        ], JSON_THROW_ON_ERROR)));

        $body = $client->request('GET', '/api/v1/me');
        $this->assertNotEmpty($body);
    }

    public function test_bubble_up_non_401_errors(): void
    {
        $httpClient = new MockHttpClient();
        $psr17 = new Psr17Factory();
        // Disable retries to ensure 500 bubbles immediately
        $config = new Config('ua/1.0; contact admin@example.com', timeoutSeconds: 10.0, maxRetries: 0);

        $coreClient = new RedditApiClient($httpClient, $psr17, $psr17, $config, new NullLogger());
        $auth = new Auth($httpClient, $psr17, $psr17, $config, new NullLogger());
        $storage = new InMemoryTokenStorage();
        $token = new Token('u_1', 'expired', 'refresh-1', time() - 10, ['read'], '42', null);
        $storage->save($token);
        $refresher = new TokenRefresher($auth, $storage, 'client', 'secret', new NullLogger());
        $client = new AutoRefreshingClient($coreClient, $refresher, $token, new NullLogger());

        $httpClient->addResponse(new Response(500, [], 'err'));

        $this->expectException(RedditApiException::class);
        $client->request('GET', '/api/v1/me');
    }
}

