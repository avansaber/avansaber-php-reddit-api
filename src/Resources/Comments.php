<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Resources;

use Avansaber\RedditApi\Data\Comment;
use Avansaber\RedditApi\Data\Link;
use Avansaber\RedditApi\Http\RedditApiClient;

final class Comments
{
    public function __construct(private readonly RedditApiClient $client)
    {
    }

    /**
     * Get comments for a post.
     *
     * @param string $subredditName Subreddit name (without r/ prefix)
     * @param string $articleId The post ID (without t3_ prefix)
     * @param array{sort?: string, limit?: int, depth?: int, context?: int, showedits?: bool, showmore?: bool, threaded?: bool} $options
     *        sort: confidence, top, new, controversial, old, qa
     * @return array{post: Link, comments: array<Comment>}
     */
    public function get(string $subredditName, string $articleId, array $options = []): array
    {
        $params = [];
        if (isset($options['sort'])) {
            $params['sort'] = $options['sort'];
        }
        if (isset($options['limit'])) {
            $params['limit'] = $options['limit'];
        }
        if (isset($options['depth'])) {
            $params['depth'] = $options['depth'];
        }
        if (isset($options['context'])) {
            $params['context'] = $options['context'];
        }
        if (isset($options['showedits'])) {
            $params['showedits'] = $options['showedits'] ? 'true' : 'false';
        }
        if (isset($options['showmore'])) {
            $params['showmore'] = $options['showmore'] ? 'true' : 'false';
        }
        if (isset($options['threaded'])) {
            $params['threaded'] = $options['threaded'] ? 'true' : 'false';
        }

        $json = $this->client->request('GET', "/r/{$subredditName}/comments/{$articleId}.json", $params);
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        // Reddit returns an array of two listings: [0] = post, [1] = comments
        if (!is_array($decoded) || count($decoded) < 2) {
            return ['post' => $this->emptyLink(), 'comments' => []];
        }

        // Parse the post (first listing)
        $postListing = $decoded[0] ?? [];
        $postData = [];
        if (is_array($postListing) && isset($postListing['data']['children'][0]['data'])) {
            $postData = $postListing['data']['children'][0]['data'];
        }
        $post = is_array($postData) ? Search::mapLink($postData) : $this->emptyLink();

        // Parse comments (second listing)
        $commentListing = $decoded[1] ?? [];
        $commentChildren = [];
        if (is_array($commentListing) && isset($commentListing['data']['children']) && is_array($commentListing['data']['children'])) {
            $commentChildren = $commentListing['data']['children'];
        }

        $comments = $this->parseComments($commentChildren);

        return ['post' => $post, 'comments' => $comments];
    }

    /**
     * Get a specific comment and its context.
     *
     * @param string $subredditName Subreddit name (without r/ prefix)
     * @param string $articleId The post ID (without t3_ prefix)
     * @param string $commentId The comment ID (without t1_ prefix)
     * @param array{context?: int, depth?: int} $options
     * @return array{post: Link, comments: array<Comment>}
     */
    public function getComment(string $subredditName, string $articleId, string $commentId, array $options = []): array
    {
        $params = ['comment' => $commentId];
        if (isset($options['context'])) {
            $params['context'] = $options['context'];
        }
        if (isset($options['depth'])) {
            $params['depth'] = $options['depth'];
        }

        $json = $this->client->request('GET', "/r/{$subredditName}/comments/{$articleId}.json", $params);
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($decoded) || count($decoded) < 2) {
            return ['post' => $this->emptyLink(), 'comments' => []];
        }

        // Parse the post
        $postListing = $decoded[0] ?? [];
        $postData = [];
        if (is_array($postListing) && isset($postListing['data']['children'][0]['data'])) {
            $postData = $postListing['data']['children'][0]['data'];
        }
        $post = is_array($postData) ? Search::mapLink($postData) : $this->emptyLink();

        // Parse comments
        $commentListing = $decoded[1] ?? [];
        $commentChildren = [];
        if (is_array($commentListing) && isset($commentListing['data']['children']) && is_array($commentListing['data']['children'])) {
            $commentChildren = $commentListing['data']['children'];
        }

        $comments = $this->parseComments($commentChildren);

        return ['post' => $post, 'comments' => $comments];
    }

    /**
     * @param array<mixed> $children
     * @return array<Comment>
     */
    private function parseComments(array $children): array
    {
        $comments = [];
        foreach ($children as $child) {
            if (!is_array($child)) {
                continue;
            }
            // Skip "more" items (kind = "more")
            if (($child['kind'] ?? '') !== 't1') {
                continue;
            }
            $data = isset($child['data']) && is_array($child['data']) ? $child['data'] : [];
            $comments[] = User::mapComment($data);
        }
        return $comments;
    }

    private function emptyLink(): Link
    {
        return new Link(
            id: '',
            fullname: '',
            title: '',
            author: '',
            subreddit: '',
            subredditId: '',
            permalink: '',
            url: '',
            score: 0,
            ups: 0,
            downs: 0,
            numComments: 0,
            createdUtc: 0,
            isSelf: false,
            selftext: '',
            over18: false,
            spoiler: false,
            locked: false,
            stickied: false,
            archived: false,
            linkFlairText: null,
            authorFlairText: null,
            edited: false,
            thumbnail: null,
            domain: null,
        );
    }
}
