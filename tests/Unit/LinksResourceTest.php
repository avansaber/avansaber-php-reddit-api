<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Tests\Unit;

use Avansaber\RedditApi\Config\Config;
use Avansaber\RedditApi\Exceptions\RedditApiException;
use Avansaber\RedditApi\Http\RedditApiClient;
use Http\Mock\Client as MockHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class LinksResourceTest extends TestCase
{
    public function test_upvote_downvote_unvote_post_ok(): void
    {
        $http = new MockHttpClient();
        $psr17 = new Psr17Factory();
        $config = new Config('ua/1.0; contact admin@example.com');
        $client = new RedditApiClient($http, $psr17, $psr17, $config, null);
        $client->withToken('token');

        $http->addResponse(new Response(204));
        $http->addResponse(new Response(204));
        $http->addResponse(new Response(204));

        $client->links()->upvote('t3_abc');
        $client->links()->downvote('t3_abc');
        $client->links()->unvote('t3_abc');

        $this->assertTrue(true);
    }

    public function test_reply_parses_comment(): void
    {
        $http = new MockHttpClient();
        $psr17 = new Psr17Factory();
        $config = new Config('ua/1.0; contact admin@example.com');
        $client = new RedditApiClient($http, $psr17, $psr17, $config, null);
        $client->withToken('token');

        $payload = [
            'json' => [
                'data' => [
                    'things' => [
                        ['data' => [
                            'id' => 'c1',
                            'name' => 't1_c1',
                            'author' => 'me',
                            'body' => 'hi',
                            'permalink' => '/r/php/comments/1/_/c1',
                            'score' => 2,
                        ]]
                    ]
                ]
            ]
        ];
        $http->addResponse(new Response(200, ['Content-Type' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR)));

        $c = $client->links()->reply('t3_abc', 'hello');
        $this->assertSame('c1', $c->id);
        $this->assertSame('t1_c1', $c->fullname);
        $this->assertSame('hi', $c->body);
    }

    public function test_reply_throws_on_invalid_response_structure(): void
    {
        $http = new MockHttpClient();
        $psr17 = new Psr17Factory();
        $config = new Config('ua/1.0; contact admin@example.com');
        $client = new RedditApiClient($http, $psr17, $psr17, $config, null);
        $client->withToken('token');

        // Invalid response missing expected structure
        $http->addResponse(new Response(200, ['Content-Type' => 'application/json'], '{"json": {"data": {}}}'));

        $this->expectException(RedditApiException::class);
        $this->expectExceptionMessage('Invalid response structure');
        $client->links()->reply('t3_abc', 'hello');
    }

    public function test_reply_throws_on_reddit_error(): void
    {
        $http = new MockHttpClient();
        $psr17 = new Psr17Factory();
        $config = new Config('ua/1.0; contact admin@example.com');
        $client = new RedditApiClient($http, $psr17, $psr17, $config, null);
        $client->withToken('token');

        // Reddit API error response
        $payload = ['json' => ['errors' => [['RATELIMIT', 'you are doing that too much', 'ratelimit']]]];
        $http->addResponse(new Response(200, ['Content-Type' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR)));

        $this->expectException(RedditApiException::class);
        $this->expectExceptionMessage('RATELIMIT');
        $client->links()->reply('t3_abc', 'hello');
    }
}

