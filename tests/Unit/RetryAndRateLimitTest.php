<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Tests\Unit;

use Avansaber\RedditApi\Config\Config;
use Avansaber\RedditApi\Http\NoopSleeper;
use Avansaber\RedditApi\Http\RedditApiClient;
use Http\Mock\Client as MockHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class RetryAndRateLimitTest extends TestCase
{
    public function test_retries_on_500_then_succeeds(): void
    {
        $http = new MockHttpClient();
        $psr17 = new Psr17Factory();
        $config = new Config('ua/1.0; contact admin@example.com', timeoutSeconds: 10.0, maxRetries: 2);
        $client = new RedditApiClient($http, $psr17, $psr17, $config, null, '', new NoopSleeper());

        $http->addResponse(new Response(500, [], 'error'));
        $http->addResponse(new Response(200, ['Content-Type' => 'application/json'], '{}'));

        $body = $client->request('GET', '/api/v1/me');
        $this->assertSame('{}', $body);
    }

    public function test_retries_on_429_respects_retry_after(): void
    {
        $http = new MockHttpClient();
        $psr17 = new Psr17Factory();
        $config = new Config('ua/1.0; contact admin@example.com', timeoutSeconds: 10.0, maxRetries: 1);
        $client = new RedditApiClient($http, $psr17, $psr17, $config, null, '', new NoopSleeper());

        $http->addResponse(new Response(429, ['Retry-After' => '1'], 'rate limited'));
        $http->addResponse(new Response(200, ['Content-Type' => 'application/json'], '{}'));

        $body = $client->request('GET', '/api/v1/me');
        $this->assertSame('{}', $body);
    }
}

