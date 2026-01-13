<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Resources;

use Avansaber\RedditApi\Data\Link;
use Avansaber\RedditApi\Data\Listing;
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
     * Get hot posts from a subreddit.
     *
     * @param string $subredditName Subreddit name (without r/ prefix)
     * @param array{limit?: int, after?: string, before?: string, g?: string} $options
     * @return Listing<Link>
     */
    public function hot(string $subredditName, array $options = []): Listing
    {
        return $this->getListing("/r/{$subredditName}/hot.json", $options);
    }

    /**
     * Get new posts from a subreddit.
     *
     * @param string $subredditName Subreddit name (without r/ prefix)
     * @param array{limit?: int, after?: string, before?: string} $options
     * @return Listing<Link>
     */
    public function new(string $subredditName, array $options = []): Listing
    {
        return $this->getListing("/r/{$subredditName}/new.json", $options);
    }

    /**
     * Get top posts from a subreddit.
     *
     * @param string $subredditName Subreddit name (without r/ prefix)
     * @param array{limit?: int, after?: string, before?: string, t?: string} $options (t = hour, day, week, month, year, all)
     * @return Listing<Link>
     */
    public function top(string $subredditName, array $options = []): Listing
    {
        return $this->getListing("/r/{$subredditName}/top.json", $options);
    }

    /**
     * Get rising posts from a subreddit.
     *
     * @param string $subredditName Subreddit name (without r/ prefix)
     * @param array{limit?: int, after?: string, before?: string} $options
     * @return Listing<Link>
     */
    public function rising(string $subredditName, array $options = []): Listing
    {
        return $this->getListing("/r/{$subredditName}/rising.json", $options);
    }

    /**
     * Get controversial posts from a subreddit.
     *
     * @param string $subredditName Subreddit name (without r/ prefix)
     * @param array{limit?: int, after?: string, before?: string, t?: string} $options (t = hour, day, week, month, year, all)
     * @return Listing<Link>
     */
    public function controversial(string $subredditName, array $options = []): Listing
    {
        return $this->getListing("/r/{$subredditName}/controversial.json", $options);
    }

    /**
     * Subscribe to a subreddit.
     *
     * @param string $subredditName Subreddit name (without r/ prefix)
     * @param bool $skipInitialDefaults Skip adding subreddit to defaults
     */
    public function subscribe(string $subredditName, bool $skipInitialDefaults = true): void
    {
        $this->client->request('POST', '/api/subscribe', form: [
            'action' => 'sub',
            'sr_name' => $subredditName,
            'skip_initial_defaults' => $skipInitialDefaults ? 'true' : 'false',
        ]);
    }

    /**
     * Unsubscribe from a subreddit.
     *
     * @param string $subredditName Subreddit name (without r/ prefix)
     */
    public function unsubscribe(string $subredditName): void
    {
        $this->client->request('POST', '/api/subscribe', form: [
            'action' => 'unsub',
            'sr_name' => $subredditName,
        ]);
    }

    /**
     * Get the subreddit rules.
     *
     * @param string $subredditName Subreddit name (without r/ prefix)
     * @return array<int, array{kind: string, description: string, short_name: string, violation_reason: string, created_utc: float, priority: int}>
     */
    public function rules(string $subredditName): array
    {
        $json = $this->client->request('GET', "/r/{$subredditName}/about/rules.json");
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($decoded) || !isset($decoded['rules']) || !is_array($decoded['rules'])) {
            return [];
        }

        $rules = [];
        foreach ($decoded['rules'] as $rule) {
            if (!is_array($rule)) {
                continue;
            }
            $rules[] = [
                'kind' => (string) ($rule['kind'] ?? ''),
                'description' => (string) ($rule['description'] ?? ''),
                'short_name' => (string) ($rule['short_name'] ?? ''),
                'violation_reason' => (string) ($rule['violation_reason'] ?? ''),
                'created_utc' => (float) ($rule['created_utc'] ?? 0),
                'priority' => (int) ($rule['priority'] ?? 0),
            ];
        }

        return $rules;
    }

    /**
     * @param array<string, int|string> $options
     * @return Listing<Link>
     */
    private function getListing(string $endpoint, array $options): Listing
    {
        $json = $this->client->request('GET', $endpoint, $options);
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $root = is_array($decoded) ? $decoded : [];
        $data = isset($root['data']) && is_array($root['data']) ? $root['data'] : [];
        $children = isset($data['children']) && is_array($data['children']) ? $data['children'] : [];

        $items = [];
        foreach ($children as $child) {
            if (!is_array($child) || ($child['kind'] ?? '') !== 't3' || !isset($child['data']) || !is_array($child['data'])) {
                continue;
            }
            $items[] = Search::mapLink($child['data']);
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
