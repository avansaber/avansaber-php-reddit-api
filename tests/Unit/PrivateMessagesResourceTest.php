<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Tests\Unit;

use Avansaber\RedditApi\Config\Config;
use Avansaber\RedditApi\Http\RedditApiClient;
use Http\Mock\Client as MockHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class PrivateMessagesResourceTest extends TestCase
{
    public function test_inbox_listing(): void
    {
        $http = new MockHttpClient();
        $psr17 = new Psr17Factory();
        $client = new RedditApiClient($http, $psr17, $psr17, new Config('ua/1.0; contact admin@example.com'));
        $client->withToken('tkn');

        $payload = [
            'data' => [
                'after' => null,
                'before' => null,
                'children' => [
                    ['data' => ['id' => 'm1', 'name' => 't4_m1', 'author' => 'u2', 'subject' => 'Hello', 'body' => 'Hi there', 'created_utc' => 123.0]],
                ],
            ],
        ];
        $http->addResponse(new Response(200, ['Content-Type' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR)));

        $listing = (new \Avansaber\RedditApi\Resources\PrivateMessages($client))->inbox(['limit' => 1]);
        $this->assertCount(1, $listing->items);
        $this->assertSame('m1', $listing->items[0]['id']);
        $this->assertSame('Hello', $listing->items[0]['subject']);
    }
}


