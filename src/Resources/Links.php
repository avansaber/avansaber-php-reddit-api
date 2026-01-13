<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Resources;

use Avansaber\RedditApi\Data\Comment;
use Avansaber\RedditApi\Data\Link;
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
    }

    /**
     * Reply to a post or comment.
     */
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

        if (!isset($thing['id']) || !isset($thing['name'])) {
            throw new RedditApiException(
                'Missing required fields (id, name) in comment response',
                0,
                $json
            );
        }

        return User::mapComment($thing);
    }

    /**
     * Submit a new text post (self post).
     *
     * @param string $subreddit Subreddit name (without r/ prefix)
     * @param string $title Post title
     * @param string $text Post body (markdown)
     * @param array{flair_id?: string, flair_text?: string, nsfw?: bool, spoiler?: bool, sendreplies?: bool} $options
     * @return Link The created post
     */
    public function submitText(string $subreddit, string $title, string $text, array $options = []): Link
    {
        $form = [
            'sr' => $subreddit,
            'kind' => 'self',
            'title' => $title,
            'text' => $text,
            'api_type' => 'json',
            'sendreplies' => $options['sendreplies'] ?? true,
        ];

        if (isset($options['flair_id'])) {
            $form['flair_id'] = $options['flair_id'];
        }
        if (isset($options['flair_text'])) {
            $form['flair_text'] = $options['flair_text'];
        }
        if (isset($options['nsfw']) && $options['nsfw']) {
            $form['nsfw'] = true;
        }
        if (isset($options['spoiler']) && $options['spoiler']) {
            $form['spoiler'] = true;
        }

        return $this->submit($form);
    }

    /**
     * Submit a new link post.
     *
     * @param string $subreddit Subreddit name (without r/ prefix)
     * @param string $title Post title
     * @param string $url Link URL
     * @param array{flair_id?: string, flair_text?: string, nsfw?: bool, spoiler?: bool, sendreplies?: bool, resubmit?: bool} $options
     * @return Link The created post
     */
    public function submitLink(string $subreddit, string $title, string $url, array $options = []): Link
    {
        $form = [
            'sr' => $subreddit,
            'kind' => 'link',
            'title' => $title,
            'url' => $url,
            'api_type' => 'json',
            'sendreplies' => $options['sendreplies'] ?? true,
            'resubmit' => $options['resubmit'] ?? false,
        ];

        if (isset($options['flair_id'])) {
            $form['flair_id'] = $options['flair_id'];
        }
        if (isset($options['flair_text'])) {
            $form['flair_text'] = $options['flair_text'];
        }
        if (isset($options['nsfw']) && $options['nsfw']) {
            $form['nsfw'] = true;
        }
        if (isset($options['spoiler']) && $options['spoiler']) {
            $form['spoiler'] = true;
        }

        return $this->submit($form);
    }

    /**
     * @param array<string, mixed> $form
     */
    private function submit(array $form): Link
    {
        $json = $this->client->request('POST', '/api/submit', [], [], $form);
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        // Check for errors
        $errors = $decoded['json']['errors'] ?? [];
        if (!empty($errors)) {
            $errorMsg = is_array($errors[0]) ? implode(': ', $errors[0]) : (string) $errors[0];
            throw new RedditApiException('Reddit API error: ' . $errorMsg, 0, $json);
        }

        $data = $decoded['json']['data'] ?? [];
        if (!isset($data['id']) || !isset($data['name'])) {
            throw new RedditApiException('Invalid submit response', 0, $json);
        }

        // Reddit returns minimal data on submit, construct a partial Link
        return new Link(
            id: (string) $data['id'],
            fullname: (string) $data['name'],
            title: (string) ($form['title'] ?? ''),
            author: '', // Not returned by API
            subreddit: (string) ($form['sr'] ?? ''),
            subredditId: '',
            permalink: (string) ($data['url'] ?? ''),
            url: (string) ($data['url'] ?? ''),
            score: 1,
            ups: 1,
            downs: 0,
            numComments: 0,
            createdUtc: (float) time(),
            isSelf: ($form['kind'] ?? '') === 'self',
            selftext: (string) ($form['text'] ?? ''),
            over18: (bool) ($form['nsfw'] ?? false),
            spoiler: (bool) ($form['spoiler'] ?? false),
            locked: false,
            stickied: false,
            archived: false,
            linkFlairText: isset($form['flair_text']) ? (string) $form['flair_text'] : null,
            authorFlairText: null,
            edited: false,
            thumbnail: null,
            domain: null,
        );
    }

    /**
     * Edit the text of a self post or comment.
     *
     * @param string|Fullname $fullname The fullname of the post/comment to edit
     * @param string $text New text content (markdown)
     */
    public function edit(string|Fullname $fullname, string $text): void
    {
        $json = $this->client->request('POST', '/api/editusertext', [], [], [
            'thing_id' => (string) $fullname,
            'text' => $text,
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
     * Delete a post or comment.
     *
     * @param string|Fullname $fullname The fullname of the post/comment to delete
     */
    public function delete(string|Fullname $fullname): void
    {
        $this->client->request('POST', '/api/del', [], [], [
            'id' => (string) $fullname,
        ]);
    }

    /**
     * Save a post or comment.
     *
     * @param string|Fullname $fullname The fullname of the post/comment to save
     * @param string|null $category Optional save category
     */
    public function save(string|Fullname $fullname, ?string $category = null): void
    {
        $form = ['id' => (string) $fullname];
        if ($category !== null) {
            $form['category'] = $category;
        }
        $this->client->request('POST', '/api/save', [], [], $form);
    }

    /**
     * Unsave a post or comment.
     *
     * @param string|Fullname $fullname The fullname of the post/comment to unsave
     */
    public function unsave(string|Fullname $fullname): void
    {
        $this->client->request('POST', '/api/unsave', [], [], [
            'id' => (string) $fullname,
        ]);
    }

    /**
     * Hide a post from listings.
     *
     * @param string|Fullname $fullname The fullname of the post to hide
     */
    public function hide(string|Fullname $fullname): void
    {
        $this->client->request('POST', '/api/hide', [], [], [
            'id' => (string) $fullname,
        ]);
    }

    /**
     * Unhide a post.
     *
     * @param string|Fullname $fullname The fullname of the post to unhide
     */
    public function unhide(string|Fullname $fullname): void
    {
        $this->client->request('POST', '/api/unhide', [], [], [
            'id' => (string) $fullname,
        ]);
    }
}
