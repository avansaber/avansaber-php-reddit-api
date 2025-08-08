<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Resources;

use Avansaber\RedditApi\Data\Subreddit as SubredditDTO;
use Avansaber\RedditApi\Http\RedditApiClient;

final class Subreddit
{
    public function __construct(private readonly RedditApiClient $client)
    {
    }

    public function about(string $subredditName): SubredditDTO
    {
        $json = $this->client->request('GET', "/r/{$subredditName}/about.json");
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $d = $decoded['data'] ?? [];

        return new SubredditDTO(
            id: (string) ($d['id'] ?? ''),
            name: (string) ($d['display_name'] ?? ''),
            title: (string) ($d['title'] ?? ''),
            publicDescription: (string) ($d['public_description'] ?? ''),
            subscribers: (int) ($d['subscribers'] ?? 0),
            over18: (bool) ($d['over18'] ?? false),
            url: (string) ($d['url'] ?? ''),
        );
    }
}

