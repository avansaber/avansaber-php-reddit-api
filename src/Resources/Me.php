<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Resources;

use Avansaber\RedditApi\Data\User;
use Avansaber\RedditApi\Http\RedditApiClient;

final class Me
{
    public function __construct(private readonly RedditApiClient $client)
    {
    }

    public function get(): User
    {
        $json = $this->client->request('GET', '/api/v1/me');
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $d = is_array($data) ? $data : [];

        return \Avansaber\RedditApi\Resources\User::mapUser($d);
    }
}
