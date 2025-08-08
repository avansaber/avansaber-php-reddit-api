<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Resources;

use Avansaber\RedditApi\Http\RedditApiClient;

final class Flair
{
    public function __construct(private readonly RedditApiClient $client)
    {
    }

    /**
     * Fetch subreddit flair settings (simplified array shape for now).
     *
     * @return array<int, array<string,mixed>>
     */
    public function get(string $subredditName): array
    {
        // Note: Flair endpoints vary; using /r/{subreddit}/api/flairselector.json as a placeholder
        $json = $this->client->request('GET', "/r/{$subredditName}/api/flairselector.json");
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        // Return raw decoded for now (DTOs can be added later)
        return is_array($decoded) ? $decoded : [];
    }
}


