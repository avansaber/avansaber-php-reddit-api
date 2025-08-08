<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Tests\Unit;

use Avansaber\RedditApi\Config\Config;
use Avansaber\RedditApi\Http\RedditApiClient;
use Http\Mock\Client as MockHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class SubredditResourceTest extends TestCase
{
    public function test_about_parses_subreddit_dto(): void
    {
        $http = new MockHttpClient();
        $psr17 = new Psr17Factory();
        $config = new Config('ua/1.0; contact admin@example.com');
        $client = new RedditApiClient($http, $psr17, $psr17, $config, null);
        $client->withToken('token');

        $payload = [
            'data' => [
                'id' => 't5_2qh33',
                'display_name' => 'php',
                'title' => 'PHP',
                'public_description' => 'All about PHP',
                'subscribers' => 123,
                'over18' => false,
                'url' => '/r/php/',
            ],
        ];
        $http->addResponse(new Response(200, ['Content-Type' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR)));

        $sr = $client->subreddit()->about('php');
        $this->assertSame('php', $sr->name);
        $this->assertSame('PHP', $sr->title);
        $this->assertSame(123, $sr->subscribers);
    }
}

