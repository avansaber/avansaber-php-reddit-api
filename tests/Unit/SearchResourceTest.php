<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Tests\Unit;

use Avansaber\RedditApi\Config\Config;
use Avansaber\RedditApi\Http\RedditApiClient;
use Http\Mock\Client as MockHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class SearchResourceTest extends TestCase
{
    public function test_search_get_parses_listing(): void
    {
        $httpClient = new MockHttpClient();
        $psr17 = new Psr17Factory();
        $config = new Config('ua/1.0; contact admin@example.com');

        $client = new RedditApiClient($httpClient, $psr17, $psr17, $config, null);
        $client->withToken('token');

        $payload = [
            'data' => [
                'after' => 't3_after',
                'before' => null,
                'children' => [
                    ['kind' => 't3', 'data' => [
                        'id' => 'abc', 'name' => 't3_abc', 'title' => 'Hello', 'author' => 'me', 'subreddit' => 'php', 'permalink' => '/r/php/abc', 'url' => 'https://example.com', 'score' => 10,
                    ]],
                    ['kind' => 't1', 'data' => ['id' => 'comment']],
                ],
            ],
        ];

        $httpClient->addResponse(new Response(200, ['Content-Type' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR)));

        $listing = $client->search()->get('hello');
        $this->assertSame('t3_after', $listing->after);
        $this->assertNull($listing->before);
        $this->assertCount(1, $listing->items);
        $this->assertSame('abc', $listing->items[0]->id);
        $this->assertSame('Hello', $listing->items[0]->title);
    }
}

