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

        return self::mapUser($d);
    }

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
            $items[] = self::mapComment($c);
        }

        return new Listing(
            items: $items,
            after: isset($data['after']) && is_string($data['after']) ? $data['after'] : null,
            before: isset($data['before']) && is_string($data['before']) ? $data['before'] : null,
        );
    }

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
            $items[] = Search::mapLink($c);
        }

        return new Listing(
            items: $items,
            after: isset($data['after']) && is_string($data['after']) ? $data['after'] : null,
            before: isset($data['before']) && is_string($data['before']) ? $data['before'] : null,
        );
    }

    /**
     * @param array<string, mixed> $d
     */
    public static function mapUser(array $d): UserDTO
    {
        return new UserDTO(
            id: (string) ($d['id'] ?? ''),
            name: (string) ($d['name'] ?? ''),
            createdUtc: (float) ($d['created_utc'] ?? 0),
            linkKarma: (int) ($d['link_karma'] ?? 0),
            commentKarma: (int) ($d['comment_karma'] ?? 0),
            totalKarma: (int) ($d['total_karma'] ?? 0),
            isEmployee: (bool) ($d['is_employee'] ?? false),
            isMod: (bool) ($d['is_mod'] ?? false),
            isGold: (bool) ($d['is_gold'] ?? false),
            verified: (bool) ($d['verified'] ?? false),
            hasVerifiedEmail: (bool) ($d['has_verified_email'] ?? false),
            iconImg: isset($d['icon_img']) && is_string($d['icon_img']) ? $d['icon_img'] : null,
            over18: (bool) ($d['over_18'] ?? false),
            isSuspended: (bool) ($d['is_suspended'] ?? false),
        );
    }

    /**
     * @param array<string, mixed> $c
     */
    public static function mapComment(array $c): CommentDTO
    {
        return new CommentDTO(
            id: (string) ($c['id'] ?? ''),
            fullname: (string) ($c['name'] ?? ''),
            author: (string) ($c['author'] ?? '[deleted]'),
            body: (string) ($c['body'] ?? ''),
            bodyHtml: (string) ($c['body_html'] ?? ''),
            permalink: (string) ($c['permalink'] ?? ''),
            score: (int) ($c['score'] ?? 0),
            ups: (int) ($c['ups'] ?? 0),
            downs: (int) ($c['downs'] ?? 0),
            createdUtc: (float) ($c['created_utc'] ?? 0),
            subreddit: (string) ($c['subreddit'] ?? ''),
            subredditId: (string) ($c['subreddit_id'] ?? ''),
            parentId: (string) ($c['parent_id'] ?? ''),
            linkId: (string) ($c['link_id'] ?? ''),
            isSubmitter: (bool) ($c['is_submitter'] ?? false),
            stickied: (bool) ($c['stickied'] ?? false),
            scoreHidden: (bool) ($c['score_hidden'] ?? false),
            locked: (bool) ($c['locked'] ?? false),
            edited: $c['edited'] ?? false,
            authorFlairText: isset($c['author_flair_text']) && is_string($c['author_flair_text']) ? $c['author_flair_text'] : null,
        );
    }
}
