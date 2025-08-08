<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Resources;

use Avansaber\RedditApi\Http\RedditApiClient;

final class Moderation
{
    public function __construct(private readonly RedditApiClient $client)
    {
    }

    public function approve(string $fullname): void
    {
        $this->client->request('POST', '/api/approve', form: [
            'id' => $fullname,
        ]);
    }

    public function remove(string $fullname, bool $spam = false): void
    {
        $this->client->request('POST', '/api/remove', form: [
            'id' => $fullname,
            'spam' => $spam ? 'true' : 'false',
        ]);
    }
}


