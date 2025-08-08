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

        return new User(
            id: (string) ($data['id'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            isEmployee: (bool) ($data['is_employee'] ?? false),
            isMod: (bool) ($data['is_mod'] ?? false),
            createdUtc: (float) ($data['created_utc'] ?? 0),
        );
    }
}