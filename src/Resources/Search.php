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
            $items[] = self::mapLink($d);
        }

        return new Listing($items, is_string($after) ? $after : null, is_string($before) ? $before : null);
    }

    /**
     * @param array<string, mixed> $d
     */
    public static function mapLink(array $d): Link
    {
        return new Link(
            id: (string) ($d['id'] ?? ''),
            fullname: (string) ($d['name'] ?? ''),
            title: (string) ($d['title'] ?? ''),
            author: (string) ($d['author'] ?? '[deleted]'),
            subreddit: (string) ($d['subreddit'] ?? ''),
            subredditId: (string) ($d['subreddit_id'] ?? ''),
            permalink: (string) ($d['permalink'] ?? ''),
            url: (string) ($d['url'] ?? ''),
            score: (int) ($d['score'] ?? 0),
            ups: (int) ($d['ups'] ?? 0),
            downs: (int) ($d['downs'] ?? 0),
            numComments: (int) ($d['num_comments'] ?? 0),
            createdUtc: (float) ($d['created_utc'] ?? 0),
            isSelf: (bool) ($d['is_self'] ?? false),
            selftext: (string) ($d['selftext'] ?? ''),
            over18: (bool) ($d['over_18'] ?? false),
            spoiler: (bool) ($d['spoiler'] ?? false),
            locked: (bool) ($d['locked'] ?? false),
            stickied: (bool) ($d['stickied'] ?? false),
            archived: (bool) ($d['archived'] ?? false),
            linkFlairText: isset($d['link_flair_text']) && is_string($d['link_flair_text']) ? $d['link_flair_text'] : null,
            authorFlairText: isset($d['author_flair_text']) && is_string($d['author_flair_text']) ? $d['author_flair_text'] : null,
            edited: $d['edited'] ?? false,
            thumbnail: isset($d['thumbnail']) && is_string($d['thumbnail']) ? $d['thumbnail'] : null,
            domain: isset($d['domain']) && is_string($d['domain']) ? $d['domain'] : null,
        );
    }
}
