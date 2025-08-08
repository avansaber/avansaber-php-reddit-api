<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Tests\Unit;

use Avansaber\RedditApi\Config\Config;
use Avansaber\RedditApi\Http\RedditApiClient;
use Http\Mock\Client as MockHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class UserHistoryResourceTest extends TestCase
{
    public function test_user_comments_listing(): void
    {
        $http = new MockHttpClient();
        $psr17 = new Psr17Factory();
        $client = new RedditApiClient($http, $psr17, $psr17, new Config('ua/1.0; contact admin@example.com'));
        $client->withToken('tkn');

        $payload = [
            'data' => [
                'after' => 't1_after',
                'before' => null,
                'children' => [
                    ['data' => ['id' => 'c1', 'name' => 't1_c1', 'author' => 'u1', 'body' => 'hi', 'permalink' => '/r/php/comments/1', 'score' => 5]],
                ],
            ],
        ];
        $http->addResponse(new Response(200, ['Content-Type' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR)));

        $listing = (new \Avansaber\RedditApi\Resources\User($client))->comments('user1', ['limit' => 1]);
        $this->assertSame('t1_after', $listing->after);
        $this->assertCount(1, $listing->items);
        $this->assertSame('c1', $listing->items[0]->id);
    }

    public function test_user_submitted_listing(): void
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
                    ['data' => ['id' => 'p1', 'name' => 't3_p1', 'title' => 'T', 'author' => 'u1', 'subreddit' => 'php', 'permalink' => '/r/php/p1', 'url' => 'https://...', 'score' => 10]],
                ],
            ],
        ];
        $http->addResponse(new Response(200, ['Content-Type' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR)));

        $listing = (new \Avansaber\RedditApi\Resources\User($client))->submitted('user1', ['limit' => 1]);
        $this->assertNull($listing->after);
        $this->assertCount(1, $listing->items);
        $this->assertSame('p1', $listing->items[0]->id);
    }
}


