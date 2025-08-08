<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Tests\Unit;

use Avansaber\RedditApi\Config\Config;
use Avansaber\RedditApi\Http\RedditApiClient;
use Http\Mock\Client as MockHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class RateLimitExposureTest extends TestCase
{
    public function test_rate_limit_info_is_parsed_and_exposed(): void
    {
        $http = new MockHttpClient();
        $psr17 = new Psr17Factory();
        $client = new RedditApiClient($http, $psr17, $psr17, new Config('ua/1.0; contact admin@example.com'));

        $http->addResponse(new Response(200, [
            'x-ratelimit-remaining' => '55',
            'x-ratelimit-used' => '45',
            'x-ratelimit-reset' => '120',
            'Content-Type' => 'application/json',
        ], '{}'));

        $client->request('GET', '/api/v1/me');
        $info = $client->getLastRateLimitInfo();
        $this->assertNotNull($info);
        $this->assertSame(55.0, $info->remaining);
        $this->assertSame(45.0, $info->used);
        $this->assertSame(120.0, $info->resetSeconds);
    }
}


