<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Resources;

use Avansaber\RedditApi\Data\Link;
use Avansaber\RedditApi\Data\Listing;
use Avansaber\RedditApi\Http\RedditApiClient;

final class Search
{
    public function __construct(private readonly RedditApiClient $client)
    {
    }

    /**
     * @param array{sort?: string, t?: string, limit?: int, after?: string, before?: string, type?: string} $options
     */
    public function get(string $query, array $options = []): Listing
    {
        $params = ['q' => $query] + $options;
        $json = $this->client->request('GET', '/search.json', $params);
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $after = $decoded['data']['after'] ?? null;
        $before = $decoded['data']['before'] ?? null;
        $children = $decoded['data']['children'] ?? [];

        $items = [];
        foreach ($children as $child) {
            if (($child['kind'] ?? '') !== 't3' || !isset($child['data']) || !is_array($child['data'])) {
                continue;
            }
            $d = $child['data'];
            $items[] = new Link(
                id: (string) ($d['id'] ?? ''),
                fullname: (string) ($d['name'] ?? ''),
                title: (string) ($d['title'] ?? ''),
                author: (string) ($d['author'] ?? ''),
                subreddit: (string) ($d['subreddit'] ?? ''),
                permalink: (string) ($d['permalink'] ?? ''),
                url: (string) ($d['url'] ?? ''),
                score: (int) ($d['score'] ?? 0),
            );
        }

        return new Listing($items, is_string($after) ? $after : null, is_string($before) ? $before : null);
    }
}

