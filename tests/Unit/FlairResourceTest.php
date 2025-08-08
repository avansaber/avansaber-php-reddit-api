<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Tests\Unit;

use Avansaber\RedditApi\Config\Config;
use Avansaber\RedditApi\Http\RedditApiClient;
use Http\Mock\Client as MockHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class FlairResourceTest extends TestCase
{
    public function test_get_flair_selector_returns_array(): void
    {
        $http = new MockHttpClient();
        $psr17 = new Psr17Factory();
        $client = new RedditApiClient($http, $psr17, $psr17, new Config('ua/1.0; contact admin@example.com'));
        $client->withToken('tkn');

        $http->addResponse(new Response(200, ['Content-Type' => 'application/json'], json_encode(['current' => [], 'choices' => []], JSON_THROW_ON_ERROR)));

        $resp = (new \Avansaber\RedditApi\Resources\Flair($client))->get('php');
        $this->assertIsArray($resp);
        $this->assertArrayHasKey('choices', $resp);
    }
}


