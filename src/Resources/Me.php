<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Resources;

use Avansaber\RedditApi\Data\KarmaBreakdown;
use Avansaber\RedditApi\Data\User as UserDTO;
use Avansaber\RedditApi\Http\RedditApiClient;

final class Me
{
    public function __construct(private readonly RedditApiClient $client)
    {
    }

    /**
     * Get the current user's profile.
     */
    public function get(): UserDTO
    {
        $json = $this->client->request('GET', '/api/v1/me');
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $d = is_array($data) ? $data : [];

        return User::mapUser($d);
    }

    /**
     * Get the current user's karma breakdown by subreddit.
     *
     * @return array<KarmaBreakdown>
     */
    public function karma(): array
    {
        $json = $this->client->request('GET', '/api/v1/me/karma');
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($data) || !isset($data['data']) || !is_array($data['data'])) {
            return [];
        }

        $breakdown = [];
        foreach ($data['data'] as $item) {
            if (!is_array($item)) {
                continue;
            }
            $breakdown[] = new KarmaBreakdown(
                subreddit: (string) ($item['sr'] ?? ''),
                linkKarma: (int) ($item['link_karma'] ?? 0),
                commentKarma: (int) ($item['comment_karma'] ?? 0),
            );
        }

        return $breakdown;
    }

    /**
     * Get the current user's preferences.
     *
     * @return array<string, mixed>
     */
    public function prefs(): array
    {
        $json = $this->client->request('GET', '/api/v1/me/prefs');
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return is_array($data) ? $data : [];
    }

    /**
     * Update the current user's preferences.
     *
     * @param array<string, mixed> $prefs Key-value pairs of preferences to update
     * @return array<string, mixed> Updated preferences
     */
    public function updatePrefs(array $prefs): array
    {
        $json = $this->client->request('PATCH', '/api/v1/me/prefs', [], [
            'Content-Type' => 'application/json',
        ], $prefs);
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return is_array($data) ? $data : [];
    }

    /**
     * Get list of the user's friends.
     *
     * @return array<string>
     */
    public function friends(): array
    {
        $json = $this->client->request('GET', '/api/v1/me/friends');
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($data) || !isset($data['data']['children']) || !is_array($data['data']['children'])) {
            return [];
        }

        $friends = [];
        foreach ($data['data']['children'] as $child) {
            if (is_array($child) && isset($child['name'])) {
                $friends[] = (string) $child['name'];
            }
        }

        return $friends;
    }

    /**
     * Get list of users the current user has blocked.
     *
     * @return array<string>
     */
    public function blocked(): array
    {
        $json = $this->client->request('GET', '/prefs/blocked');
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($data) || !isset($data['data']['children']) || !is_array($data['data']['children'])) {
            return [];
        }

        $blocked = [];
        foreach ($data['data']['children'] as $child) {
            if (is_array($child) && isset($child['name'])) {
                $blocked[] = (string) $child['name'];
            }
        }

        return $blocked;
    }

    /**
     * Get trophies for the current user.
     *
     * @return array<array{name: string, description: string, icon_70: string, icon_40: string}>
     */
    public function trophies(): array
    {
        $json = $this->client->request('GET', '/api/v1/me/trophies');
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($data) || !isset($data['data']['trophies']) || !is_array($data['data']['trophies'])) {
            return [];
        }

        $trophies = [];
        foreach ($data['data']['trophies'] as $item) {
            if (!is_array($item) || !isset($item['data']) || !is_array($item['data'])) {
                continue;
            }
            $t = $item['data'];
            $trophies[] = [
                'name' => (string) ($t['name'] ?? ''),
                'description' => (string) ($t['description'] ?? ''),
                'icon_70' => (string) ($t['icon_70'] ?? ''),
                'icon_40' => (string) ($t['icon_40'] ?? ''),
            ];
        }

        return $trophies;
    }
}
