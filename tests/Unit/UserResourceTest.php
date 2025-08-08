<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Tests\Unit;

use Avansaber\RedditApi\Config\Config;
use Avansaber\RedditApi\Http\RedditApiClient;
use Http\Mock\Client as MockHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class UserResourceTest extends TestCase
{
    public function test_about_parses_user_dto(): void
    {
        $http = new MockHttpClient();
        $psr17 = new Psr17Factory();
        $config = new Config('ua/1.0; contact admin@example.com');
        $client = new RedditApiClient($http, $psr17, $psr17, $config, null);
        $client->withToken('token');

        $payload = [
            'data' => [
                'id' => 'u_abc',
                'name' => 'spez',
                'is_employee' => true,
                'is_mod' => false,
                'created_utc' => 1500000000.0,
            ],
        ];
        $http->addResponse(new Response(200, ['Content-Type' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR)));

        $u = $client->user()->about('spez');
        $this->assertSame('spez', $u->name);
        $this->assertTrue($u->isEmployee);
        $this->assertFalse($u->isMod);
    }
}

