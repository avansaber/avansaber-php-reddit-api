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
        $d = (is_array($decoded) && isset($decoded['data']) && is_array($decoded['data'])) ? $decoded['data'] : [];

        return self::mapSubreddit($d);
    }

    /**
     * @param array<string, mixed> $d
     */
    public static function mapSubreddit(array $d): SubredditDTO
    {
        return new SubredditDTO(
            id: (string) ($d['id'] ?? ''),
            name: (string) ($d['name'] ?? ''),
            displayName: (string) ($d['display_name'] ?? ''),
            title: (string) ($d['title'] ?? ''),
            publicDescription: (string) ($d['public_description'] ?? ''),
            description: (string) ($d['description'] ?? ''),
            subscribers: (int) ($d['subscribers'] ?? 0),
            activeUserCount: (int) ($d['active_user_count'] ?? $d['accounts_active'] ?? 0),
            createdUtc: (float) ($d['created_utc'] ?? 0),
            over18: (bool) ($d['over18'] ?? false),
            url: (string) ($d['url'] ?? ''),
            subredditType: (string) ($d['subreddit_type'] ?? 'public'),
            quarantine: (bool) ($d['quarantine'] ?? false),
            bannerImg: isset($d['banner_img']) && is_string($d['banner_img']) && $d['banner_img'] !== '' ? $d['banner_img'] : null,
            iconImg: isset($d['icon_img']) && is_string($d['icon_img']) && $d['icon_img'] !== '' ? $d['icon_img'] : null,
            headerImg: isset($d['header_img']) && is_string($d['header_img']) && $d['header_img'] !== '' ? $d['header_img'] : null,
            primaryColor: isset($d['primary_color']) && is_string($d['primary_color']) && $d['primary_color'] !== '' ? $d['primary_color'] : null,
            keyColor: isset($d['key_color']) && is_string($d['key_color']) && $d['key_color'] !== '' ? $d['key_color'] : null,
        );
    }
}
