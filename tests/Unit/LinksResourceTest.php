<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Tests\Unit;

use Avansaber\RedditApi\Config\Config;
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
}

