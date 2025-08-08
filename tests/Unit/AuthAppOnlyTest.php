<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Tests\Unit;

use Avansaber\RedditApi\Auth\Auth;
use Avansaber\RedditApi\Config\Config;
use Avansaber\RedditApi\Exceptions\AuthenticationException;
use Http\Mock\Client as MockHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class AuthAppOnlyTest extends TestCase
{
    public function test_app_only_returns_token(): void
    {
        $httpClient = new MockHttpClient();
        $psr17 = new Psr17Factory();
        $config = new Config('ua/1.0; contact admin@example.com');

        $auth = new Auth($httpClient, $psr17, $psr17, $config, null);

        $httpClient->addResponse(new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'access_token' => 'abc123',
            'token_type' => 'bearer',
            'expires_in' => 3600,
            'scope' => 'read identity',
        ], JSON_THROW_ON_ERROR)));

        $token = $auth->appOnly('client', 'secret', ['read', 'identity']);
        $this->assertSame('abc123', $token);
    }

    public function test_app_only_throws_on_error_status(): void
    {
        $httpClient = new MockHttpClient();
        $psr17 = new Psr17Factory();
        $config = new Config('ua/1.0; contact admin@example.com');

        $auth = new Auth($httpClient, $psr17, $psr17, $config, null);

        $httpClient->addResponse(new Response(401, ['Content-Type' => 'application/json'], '{"error":"unauthorized"}'));

        $this->expectException(AuthenticationException::class);
        $auth->appOnly('bad', 'creds');
    }
}