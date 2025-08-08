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
     * @return Listing<Link>
     */
    public function get(string $query, array $options = []): Listing
    {
        $params = ['q' => $query] + $options;
        $json = $this->client->request('GET', '/search.json', $params);
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $root = is_array($decoded) ? $decoded : [];
        $data = isset($root['data']) && is_array($root['data']) ? $root['data'] : [];
        $after = $data['after'] ?? null;
        $before = $data['before'] ?? null;
        $children = isset($data['children']) && is_array($data['children']) ? $data['children'] : [];

        $items = [];
        foreach ($children as $child) {
            if (!is_array($child) || ($child['kind'] ?? '') !== 't3' || !isset($child['data']) || !is_array($child['data'])) {
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

