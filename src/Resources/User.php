<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Resources;

use Avansaber\RedditApi\Data\User as UserDTO;
use Avansaber\RedditApi\Http\RedditApiClient;

final class User
{
    public function __construct(private readonly RedditApiClient $client)
    {
    }

    public function about(string $username): UserDTO
    {
        $json = $this->client->request('GET', "/user/{$username}/about.json");
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $d = $decoded['data'] ?? [];

        return new UserDTO(
            id: (string) ($d['id'] ?? ''),
            name: (string) ($d['name'] ?? ''),
            isEmployee: (bool) ($d['is_employee'] ?? false),
            isMod: (bool) ($d['is_mod'] ?? false),
            createdUtc: (float) ($d['created_utc'] ?? 0),
        );
    }
}

