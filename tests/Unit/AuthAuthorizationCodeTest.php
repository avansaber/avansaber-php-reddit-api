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

final class AuthAuthorizationCodeTest extends TestCase
{
    public function test_generate_pkce_pair_shapes(): void
    {
        $auth = new Auth(new MockHttpClient(), new Psr17Factory(), new Psr17Factory(), new Config('ua/1.0; contact admin@example.com'));
        $pair = $auth->generatePkcePair();
        $this->assertArrayHasKey('verifier', $pair);
        $this->assertArrayHasKey('challenge', $pair);
        $this->assertIsString($pair['verifier']);
        $this->assertIsString($pair['challenge']);
        $this->assertNotSame('', $pair['verifier']);
        $this->assertNotSame('', $pair['challenge']);
    }

    public function test_get_auth_url_includes_pkce_when_provided(): void
    {
        $auth = new Auth(new MockHttpClient(), new Psr17Factory(), new Psr17Factory(), new Config('ua/1.0; contact admin@example.com'));
        $url = $auth->getAuthUrl('client', 'https://example.com/callback', ['read','identity'], 'state123', 'challengeABC');
        $this->assertStringContainsString('code_challenge=challengeABC', $url);
        $this->assertStringContainsString('code_challenge_method=S256', $url);
        $this->assertStringContainsString('client_id=client', $url);
        $this->assertStringContainsString('response_type=code', $url);
    }

    public function test_exchange_code_success_with_pkce(): void
    {
        $http = new MockHttpClient();
        $psr17 = new Psr17Factory();
        $auth = new Auth($http, $psr17, $psr17, new Config('ua/1.0; contact admin@example.com'));

        $http->addResponse(new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'access_token' => 'user-token',
            'refresh_token' => 'refresh-token',
            'token_type' => 'bearer',
            'expires_in' => 3600,
            'scope' => 'read identity',
        ], JSON_THROW_ON_ERROR)));

        $result = $auth->getAccessTokenFromCode('client', null, 'the_code', 'https://example.com/callback', 'the_verifier');
        $this->assertSame('user-token', $result['access_token']);
        $this->assertSame('refresh-token', $result['refresh_token']);
    }

    public function test_exchange_code_throws_on_error(): void
    {
        $http = new MockHttpClient();
        $psr17 = new Psr17Factory();
        $auth = new Auth($http, $psr17, $psr17, new Config('ua/1.0; contact admin@example.com'));

        $http->addResponse(new Response(400, ['Content-Type' => 'application/json'], '{"error":"invalid_grant"}'));

        $this->expectException(AuthenticationException::class);
        $auth->getAccessTokenFromCode('client', null, 'bad_code', 'https://example.com/callback', 'verifier');
    }

    public function test_generate_state_returns_hex_string(): void
    {
        $auth = new Auth(new MockHttpClient(), new Psr17Factory(), new Psr17Factory(), new Config('ua/1.0; contact admin@example.com'));
        $state = $auth->generateState();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $state);
    }

    public function test_generate_state_is_unique(): void
    {
        $auth = new Auth(new MockHttpClient(), new Psr17Factory(), new Psr17Factory(), new Config('ua/1.0; contact admin@example.com'));
        $state1 = $auth->generateState();
        $state2 = $auth->generateState();
        $this->assertNotSame($state1, $state2);
    }

    public function test_validate_state_passes_on_match(): void
    {
        $auth = new Auth(new MockHttpClient(), new Psr17Factory(), new Psr17Factory(), new Config('ua/1.0; contact admin@example.com'));
        $auth->validateState('abc123', 'abc123');
        $this->assertTrue(true); // No exception means success
    }

    public function test_validate_state_throws_on_mismatch(): void
    {
        $auth = new Auth(new MockHttpClient(), new Psr17Factory(), new Psr17Factory(), new Config('ua/1.0; contact admin@example.com'));
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('CSRF');
        $auth->validateState('expected', 'actual');
    }

    public function test_validate_state_throws_on_empty(): void
    {
        $auth = new Auth(new MockHttpClient(), new Psr17Factory(), new Psr17Factory(), new Config('ua/1.0; contact admin@example.com'));
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('missing');
        $auth->validateState('', 'actual');
    }

    public function test_auth_url_uses_configurable_base_uri(): void
    {
        $config = new Config('ua/1.0; contact admin@example.com', 'https://oauth.reddit.com', 10.0, 3, 'https://custom.reddit.com');
        $auth = new Auth(new MockHttpClient(), new Psr17Factory(), new Psr17Factory(), $config);
        $url = $auth->getAuthUrl('client', 'https://example.com/callback', ['read'], 'state123');
        $this->assertStringStartsWith('https://custom.reddit.com/api/v1/authorize?', $url);
    }
}


