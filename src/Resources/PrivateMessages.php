<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Resources;

use Avansaber\RedditApi\Data\Listing;
use Avansaber\RedditApi\Http\RedditApiClient;

final class PrivateMessages
{
    public function __construct(private readonly RedditApiClient $client)
    {
    }

    /**
     * @param array<string, int|string> $options
     * @return Listing<array{id: string, fullname: string, author: string, subject: string, body: string, created_utc: float}>
     */
    public function inbox(array $options = []): Listing
    {
        $json = $this->client->request('GET', '/message/inbox.json', $options);
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $root = is_array($decoded) ? $decoded : [];
        $data = isset($root['data']) && is_array($root['data']) ? $root['data'] : [];
        $rawChildren = isset($data['children']) && is_array($data['children']) ? $data['children'] : [];

        $items = [];
        foreach ($rawChildren as $child) {
            if (!is_array($child)) {
                continue;
            }
            $c = isset($child['data']) && is_array($child['data']) ? $child['data'] : [];
            $items[] = [
                'id' => (string) ($c['id'] ?? ''),
                'fullname' => (string) ($c['name'] ?? ''),
                'author' => (string) ($c['author'] ?? ''),
                'subject' => (string) ($c['subject'] ?? ''),
                'body' => (string) ($c['body'] ?? ''),
                'created_utc' => (float) ($c['created_utc'] ?? 0.0),
            ];
        }

        return new Listing(
            items: $items,
            after: isset($data['after']) && is_string($data['after']) ? $data['after'] : null,
            before: isset($data['before']) && is_string($data['before']) ? $data['before'] : null,
        );
    }
}


