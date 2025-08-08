<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Tests\Unit;

use Avansaber\RedditApi\Config\Config;
use Avansaber\RedditApi\Http\RedditApiClient;
use Http\Mock\Client as MockHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class MeResourceTest extends TestCase
{
    public function test_me_get_returns_user_dto(): void
    {
        $httpClient = new MockHttpClient();
        $psr17 = new Psr17Factory();
        $config = new Config('ua/1.0; contact admin@example.com');

        $client = new RedditApiClient($httpClient, $psr17, $psr17, $config, null);
        $client->withToken('access-token');

        $httpClient->addResponse(new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'id' => 'abc',
            'name' => 'johndoe',
            'is_employee' => false,
            'is_mod' => true,
            'created_utc' => 1600000000.0,
        ], JSON_THROW_ON_ERROR)));

        $user = $client->me()->get();
        $this->assertSame('abc', $user->id);
        $this->assertSame('johndoe', $user->name);
        $this->assertTrue($user->isMod);
        $this->assertFalse($user->isEmployee);
        $this->assertSame(1600000000.0, $user->createdUtc);
    }
}