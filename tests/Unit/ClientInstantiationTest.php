<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Tests\Unit;

use Avansaber\RedditApi\Config\Config;
use Avansaber\RedditApi\Http\RedditApiClient;
use Http\Mock\Client as MockHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class ClientInstantiationTest extends TestCase
{
    public function test_it_can_be_instantiated(): void
    {
        $httpClient = new MockHttpClient();
        $psr17Factory = new Psr17Factory();
        $config = new Config(userAgent: 'avansaber-php-reddit-api/0.1; contact admin@example.com');

        $client = new RedditApiClient(
            httpClient: $httpClient,
            requestFactory: $psr17Factory,
            streamFactory: $psr17Factory,
            config: $config,
            logger: new NullLogger()
        );

        $this->assertInstanceOf(RedditApiClient::class, $client);
        $this->assertSame('avansaber-php-reddit-api/0.1; contact admin@example.com', $client->getConfig()->getUserAgent());
    }

    public function test_with_token_returns_self(): void
    {
        $httpClient = new MockHttpClient();
        $psr17Factory = new Psr17Factory();
        $config = new Config(userAgent: 'ua/1.0; contact admin@example.com');

        $client = new RedditApiClient(
            httpClient: $httpClient,
            requestFactory: $psr17Factory,
            streamFactory: $psr17Factory,
            config: $config,
            logger: null
        );

        $result = $client->withToken('token');
        $this->assertSame($client, $result);
    }
}