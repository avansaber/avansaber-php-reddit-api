<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Resources;

use Avansaber\RedditApi\Data\User as UserDTO;
use Avansaber\RedditApi\Data\Listing;
use Avansaber\RedditApi\Data\Link as LinkDTO;
use Avansaber\RedditApi\Data\Comment as CommentDTO;
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
        $root = is_array($decoded) ? $decoded : [];
        $d = isset($root['data']) && is_array($root['data']) ? $root['data'] : [];

        return new UserDTO(
            id: (string) ($d['id'] ?? ''),
            name: (string) ($d['name'] ?? ''),
            isEmployee: (bool) ($d['is_employee'] ?? false),
            isMod: (bool) ($d['is_mod'] ?? false),
            createdUtc: (float) ($d['created_utc'] ?? 0),
        );
    }

    /**
     * @return Listing<CommentDTO>
     */
    /**
     * @param array<string, int|string> $options
     * @return Listing<CommentDTO>
     */
    public function comments(string $username, array $options = []): Listing
    {
        $json = $this->client->request('GET', "/user/{$username}/comments.json", $options);
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $root = is_array($decoded) ? $decoded : [];
        $data = isset($root['data']) && is_array($root['data']) ? $root['data'] : [];
        $children = isset($data['children']) && is_array($data['children']) ? $data['children'] : [];

        $items = [];
        foreach ($children as $child) {
            if (!is_array($child)) {
                continue;
            }
            $c = isset($child['data']) && is_array($child['data']) ? $child['data'] : [];
            $items[] = new CommentDTO(
                id: (string) ($c['id'] ?? ''),
                fullname: (string) ($c['name'] ?? ''),
                author: (string) ($c['author'] ?? ''),
                body: (string) ($c['body'] ?? ''),
                permalink: (string) ($c['permalink'] ?? ''),
                score: (int) ($c['score'] ?? 0),
            );
        }

        return new Listing(
            items: $items,
            after: isset($data['after']) && is_string($data['after']) ? $data['after'] : null,
            before: isset($data['before']) && is_string($data['before']) ? $data['before'] : null,
        );
    }

    /**
     * @return Listing<LinkDTO>
     */
    /**
     * @param array<string, int|string> $options
     * @return Listing<LinkDTO>
     */
    public function submitted(string $username, array $options = []): Listing
    {
        $json = $this->client->request('GET', "/user/{$username}/submitted.json", $options);
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $root = is_array($decoded) ? $decoded : [];
        $data = isset($root['data']) && is_array($root['data']) ? $root['data'] : [];
        $children = isset($data['children']) && is_array($data['children']) ? $data['children'] : [];

        $items = [];
        foreach ($children as $child) {
            if (!is_array($child)) {
                continue;
            }
            $c = isset($child['data']) && is_array($child['data']) ? $child['data'] : [];
            $items[] = new LinkDTO(
                id: (string) ($c['id'] ?? ''),
                fullname: (string) ($c['name'] ?? ''),
                title: (string) ($c['title'] ?? ''),
                author: (string) ($c['author'] ?? ''),
                subreddit: (string) ($c['subreddit'] ?? ''),
                permalink: (string) ($c['permalink'] ?? ''),
                url: (string) ($c['url'] ?? ''),
                score: (int) ($c['score'] ?? 0),
            );
        }

        return new Listing(
            items: $items,
            after: isset($data['after']) && is_string($data['after']) ? $data['after'] : null,
            before: isset($data['before']) && is_string($data['before']) ? $data['before'] : null,
        );
    }
}

