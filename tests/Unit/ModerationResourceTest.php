<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Tests\Unit;

use Avansaber\RedditApi\Config\Config;
use Avansaber\RedditApi\Http\RedditApiClient;
use Http\Mock\Client as MockHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class ModerationResourceTest extends TestCase
{
    public function test_approve_and_remove_do_not_throw_on_200(): void
    {
        $http = new MockHttpClient();
        $psr17 = new Psr17Factory();
        $client = new RedditApiClient($http, $psr17, $psr17, new Config('ua/1.0; contact admin@example.com'));
        $client->withToken('tkn');

        // Approve response then Remove response
        $http->addResponse(new Response(200, ['Content-Type' => 'application/json'], '{}'));
        $http->addResponse(new Response(200, ['Content-Type' => 'application/json'], '{}'));

        $mod = new \Avansaber\RedditApi\Resources\Moderation($client);
        $mod->approve('t3_abc');
        $mod->remove('t3_abc', true);
        $this->assertTrue(true);
    }
}


