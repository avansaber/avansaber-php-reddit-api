<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Resources;

use Avansaber\RedditApi\Data\Listing;
use Avansaber\RedditApi\Data\Message;
use Avansaber\RedditApi\Exceptions\RedditApiException;
use Avansaber\RedditApi\Http\RedditApiClient;
use Avansaber\RedditApi\Value\Fullname;

final class PrivateMessages
{
    public function __construct(private readonly RedditApiClient $client)
    {
    }

    /**
     * Get messages from the inbox (received messages and comment replies).
     *
     * @param array<string, int|string> $options
     * @return Listing<Message>
     */
    public function inbox(array $options = []): Listing
    {
        return $this->getMessages('/message/inbox.json', $options);
    }

    /**
     * Get unread messages only.
     *
     * @param array<string, int|string> $options
     * @return Listing<Message>
     */
    public function unread(array $options = []): Listing
    {
        return $this->getMessages('/message/unread.json', $options);
    }

    /**
     * Get sent messages.
     *
     * @param array<string, int|string> $options
     * @return Listing<Message>
     */
    public function sent(array $options = []): Listing
    {
        return $this->getMessages('/message/sent.json', $options);
    }

    /**
     * Send a private message to a user.
     *
     * @param string $to Username of the recipient
     * @param string $subject Message subject
     * @param string $body Message body (markdown)
     */
    public function compose(string $to, string $subject, string $body): void
    {
        $json = $this->client->request('POST', '/api/compose', [], [], [
            'to' => $to,
            'subject' => $subject,
            'text' => $body,
            'api_type' => 'json',
        ]);
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $errors = $decoded['json']['errors'] ?? [];
        if (!empty($errors)) {
            $errorMsg = is_array($errors[0]) ? implode(': ', $errors[0]) : (string) $errors[0];
            throw new RedditApiException('Reddit API error: ' . $errorMsg, 0, $json);
        }
    }

    /**
     * Mark one or more messages as read.
     *
     * @param string|Fullname|array<string|Fullname> $fullnames Message fullname(s) to mark as read
     */
    public function markRead(string|Fullname|array $fullnames): void
    {
        $ids = $this->normalizeFullnames($fullnames);
        $this->client->request('POST', '/api/read_message', [], [], [
            'id' => implode(',', $ids),
        ]);
    }

    /**
     * Mark one or more messages as unread.
     *
     * @param string|Fullname|array<string|Fullname> $fullnames Message fullname(s) to mark as unread
     */
    public function markUnread(string|Fullname|array $fullnames): void
    {
        $ids = $this->normalizeFullnames($fullnames);
        $this->client->request('POST', '/api/unread_message', [], [], [
            'id' => implode(',', $ids),
        ]);
    }

    /**
     * Delete a message from the inbox.
     *
     * @param string|Fullname $fullname Message fullname to delete
     */
    public function delete(string|Fullname $fullname): void
    {
        $this->client->request('POST', '/api/del_msg', [], [], [
            'id' => (string) $fullname,
        ]);
    }

    /**
     * Block a user who sent you a message.
     *
     * @param string|Fullname $fullname Fullname of a thing the user created (message, comment, post)
     */
    public function blockAuthor(string|Fullname $fullname): void
    {
        $this->client->request('POST', '/api/block', [], [], [
            'id' => (string) $fullname,
        ]);
    }

    /**
     * @param array<string, int|string> $options
     * @return Listing<Message>
     */
    private function getMessages(string $endpoint, array $options): Listing
    {
        $json = $this->client->request('GET', $endpoint, $options);
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
            $items[] = self::mapMessage($c);
        }

        return new Listing(
            items: $items,
            after: isset($data['after']) && is_string($data['after']) ? $data['after'] : null,
            before: isset($data['before']) && is_string($data['before']) ? $data['before'] : null,
        );
    }

    /**
     * @param array<string, mixed> $c
     */
    public static function mapMessage(array $c): Message
    {
        return new Message(
            id: (string) ($c['id'] ?? ''),
            fullname: (string) ($c['name'] ?? ''),
            author: (string) ($c['author'] ?? ''),
            subject: (string) ($c['subject'] ?? ''),
            body: (string) ($c['body'] ?? ''),
            bodyHtml: (string) ($c['body_html'] ?? ''),
            createdUtc: (float) ($c['created_utc'] ?? 0.0),
            dest: isset($c['dest']) && is_string($c['dest']) ? $c['dest'] : null,
            parentId: isset($c['parent_id']) && is_string($c['parent_id']) ? $c['parent_id'] : null,
            isNew: (bool) ($c['new'] ?? false),
            wasComment: (bool) ($c['was_comment'] ?? false),
            context: isset($c['context']) && is_string($c['context']) ? $c['context'] : null,
            subreddit: isset($c['subreddit']) && is_string($c['subreddit']) ? $c['subreddit'] : null,
        );
    }

    /**
     * @param string|Fullname|array<string|Fullname> $fullnames
     * @return array<string>
     */
    private function normalizeFullnames(string|Fullname|array $fullnames): array
    {
        if (!is_array($fullnames)) {
            return [(string) $fullnames];
        }
        return array_map(fn ($f) => (string) $f, $fullnames);
    }
}
