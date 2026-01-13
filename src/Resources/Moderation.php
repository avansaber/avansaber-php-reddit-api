<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Resources;

use Avansaber\RedditApi\Http\RedditApiClient;
use Avansaber\RedditApi\Value\Fullname;

final class Moderation
{
    public function __construct(private readonly RedditApiClient $client)
    {
    }

    /**
     * Approve a post or comment.
     *
     * @param string|Fullname $fullname The fullname of the item to approve
     */
    public function approve(string|Fullname $fullname): void
    {
        $this->client->request('POST', '/api/approve', form: [
            'id' => (string) $fullname,
        ]);
    }

    /**
     * Remove a post or comment.
     *
     * @param string|Fullname $fullname The fullname of the item to remove
     * @param bool $spam Mark as spam (affects spam filter training)
     */
    public function remove(string|Fullname $fullname, bool $spam = false): void
    {
        $this->client->request('POST', '/api/remove', form: [
            'id' => (string) $fullname,
            'spam' => $spam ? 'true' : 'false',
        ]);
    }

    /**
     * Lock a post or comment (prevent new comments).
     *
     * @param string|Fullname $fullname The fullname of the item to lock
     */
    public function lock(string|Fullname $fullname): void
    {
        $this->client->request('POST', '/api/lock', form: [
            'id' => (string) $fullname,
        ]);
    }

    /**
     * Unlock a post or comment.
     *
     * @param string|Fullname $fullname The fullname of the item to unlock
     */
    public function unlock(string|Fullname $fullname): void
    {
        $this->client->request('POST', '/api/unlock', form: [
            'id' => (string) $fullname,
        ]);
    }

    /**
     * Sticky a post in a subreddit (pin to top).
     *
     * @param string|Fullname $fullname The fullname of the post to sticky
     * @param int $num Sticky slot (1 or 2, subreddits can have 2 stickied posts)
     * @param bool $toProfile If true, sticky to user profile instead of subreddit
     */
    public function sticky(string|Fullname $fullname, int $num = 1, bool $toProfile = false): void
    {
        $this->client->request('POST', '/api/set_subreddit_sticky', form: [
            'id' => (string) $fullname,
            'state' => 'true',
            'num' => $num,
            'to_profile' => $toProfile ? 'true' : 'false',
        ]);
    }

    /**
     * Unsticky a post.
     *
     * @param string|Fullname $fullname The fullname of the post to unsticky
     */
    public function unsticky(string|Fullname $fullname): void
    {
        $this->client->request('POST', '/api/set_subreddit_sticky', form: [
            'id' => (string) $fullname,
            'state' => 'false',
        ]);
    }

    /**
     * Distinguish a comment or post as a moderator.
     *
     * @param string|Fullname $fullname The fullname of the item
     * @param string $how 'yes' for mod, 'admin' for admin, 'no' to remove, 'special' for special
     * @param bool $sticky Sticky the comment (only works for top-level comments)
     */
    public function distinguish(string|Fullname $fullname, string $how = 'yes', bool $sticky = false): void
    {
        $this->client->request('POST', '/api/distinguish', form: [
            'id' => (string) $fullname,
            'how' => $how,
            'sticky' => $sticky ? 'true' : 'false',
        ]);
    }

    /**
     * Ignore reports on a post or comment.
     *
     * @param string|Fullname $fullname The fullname of the item
     */
    public function ignoreReports(string|Fullname $fullname): void
    {
        $this->client->request('POST', '/api/ignore_reports', form: [
            'id' => (string) $fullname,
        ]);
    }

    /**
     * Unignore reports on a post or comment.
     *
     * @param string|Fullname $fullname The fullname of the item
     */
    public function unignoreReports(string|Fullname $fullname): void
    {
        $this->client->request('POST', '/api/unignore_reports', form: [
            'id' => (string) $fullname,
        ]);
    }

    /**
     * Mark a post as NSFW.
     *
     * @param string|Fullname $fullname The fullname of the post
     */
    public function markNsfw(string|Fullname $fullname): void
    {
        $this->client->request('POST', '/api/marknsfw', form: [
            'id' => (string) $fullname,
        ]);
    }

    /**
     * Unmark a post as NSFW.
     *
     * @param string|Fullname $fullname The fullname of the post
     */
    public function unmarkNsfw(string|Fullname $fullname): void
    {
        $this->client->request('POST', '/api/unmarknsfw', form: [
            'id' => (string) $fullname,
        ]);
    }

    /**
     * Mark a post as a spoiler.
     *
     * @param string|Fullname $fullname The fullname of the post
     */
    public function markSpoiler(string|Fullname $fullname): void
    {
        $this->client->request('POST', '/api/spoiler', form: [
            'id' => (string) $fullname,
        ]);
    }

    /**
     * Unmark a post as a spoiler.
     *
     * @param string|Fullname $fullname The fullname of the post
     */
    public function unmarkSpoiler(string|Fullname $fullname): void
    {
        $this->client->request('POST', '/api/unspoiler', form: [
            'id' => (string) $fullname,
        ]);
    }
}
