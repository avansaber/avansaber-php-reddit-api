<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Resources;

use Avansaber\RedditApi\Data\Comment;
use Avansaber\RedditApi\Exceptions\RedditApiException;
use Avansaber\RedditApi\Http\RedditApiClient;
use Avansaber\RedditApi\Value\Fullname;

final class Links
{
    public function __construct(private readonly RedditApiClient $client)
    {
    }

    public function upvote(string|Fullname $fullname): void
    {
        $this->vote((string) $fullname, 1);
    }

    public function downvote(string|Fullname $fullname): void
    {
        $this->vote((string) $fullname, -1);
    }

    public function unvote(string|Fullname $fullname): void
    {
        $this->vote((string) $fullname, 0);
    }

    private function vote(string $fullname, int $dir): void
    {
        $this->client->request('POST', '/api/vote', [], [], [
            'id' => $fullname,
            'dir' => $dir,
            'api_type' => 'json',
        ]);
        // For simplicity, we are not parsing body; Reddit returns 204/200
    }

    public function reply(string|Fullname $fullname, string $text): Comment
    {
        $json = $this->client->request('POST', '/api/comment', [], [], [
            'thing_id' => (string) $fullname,
            'text' => $text,
            'api_type' => 'json',
        ]);
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        // Validate response structure
        if (!isset($decoded['json']['data']['things'][0]['data'])) {
            // Check for errors in response
            $errors = $decoded['json']['errors'] ?? [];
            if (!empty($errors)) {
                $errorMsg = is_array($errors[0]) ? implode(': ', $errors[0]) : (string) $errors[0];
                throw new RedditApiException('Reddit API error: ' . $errorMsg, 0, $json);
            }
            throw new RedditApiException(
                'Invalid response structure from Reddit comment API',
                0,
                $json
            );
        }

        $thing = $decoded['json']['data']['things'][0]['data'];

        // Validate required fields exist
        if (!isset($thing['id']) || !isset($thing['name'])) {
            throw new RedditApiException(
                'Missing required fields (id, name) in comment response',
                0,
                $json
            );
        }

        return new Comment(
            id: (string) $thing['id'],
            fullname: (string) $thing['name'],
            author: (string) ($thing['author'] ?? '[deleted]'),
            body: (string) ($thing['body'] ?? ''),
            permalink: (string) ($thing['permalink'] ?? ''),
            score: (int) ($thing['score'] ?? 0),
        );
    }
}

