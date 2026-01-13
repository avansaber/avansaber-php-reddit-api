<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Resources;

use Avansaber\RedditApi\Data\Flair as FlairDTO;
use Avansaber\RedditApi\Exceptions\RedditApiException;
use Avansaber\RedditApi\Http\RedditApiClient;
use Avansaber\RedditApi\Value\Fullname;

final class Flair
{
    public function __construct(private readonly RedditApiClient $client)
    {
    }

    /**
     * Get available link flair templates for a subreddit.
     *
     * @param string $subredditName Subreddit name (without r/ prefix)
     * @return array<FlairDTO>
     */
    public function getLinkFlairs(string $subredditName): array
    {
        $json = $this->client->request('GET', "/r/{$subredditName}/api/link_flair_v2.json");
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($decoded)) {
            return [];
        }

        return array_map(fn (array $f) => self::mapFlair($f), $decoded);
    }

    /**
     * Get available user flair templates for a subreddit.
     *
     * @param string $subredditName Subreddit name (without r/ prefix)
     * @return array<FlairDTO>
     */
    public function getUserFlairs(string $subredditName): array
    {
        $json = $this->client->request('GET', "/r/{$subredditName}/api/user_flair_v2.json");
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($decoded)) {
            return [];
        }

        return array_map(fn (array $f) => self::mapFlair($f), $decoded);
    }

    /**
     * Set the flair on a link (post).
     *
     * @param string $subredditName Subreddit name (without r/ prefix)
     * @param string|Fullname $linkFullname The fullname of the post (t3_...)
     * @param string|null $flairTemplateId Flair template ID (from getLinkFlairs)
     * @param string|null $text Custom flair text (if template allows editing)
     */
    public function setLinkFlair(
        string $subredditName,
        string|Fullname $linkFullname,
        ?string $flairTemplateId = null,
        ?string $text = null
    ): void {
        $form = [
            'api_type' => 'json',
            'link' => (string) $linkFullname,
        ];

        if ($flairTemplateId !== null) {
            $form['flair_template_id'] = $flairTemplateId;
        }
        if ($text !== null) {
            $form['text'] = $text;
        }

        $json = $this->client->request('POST', "/r/{$subredditName}/api/selectflair", form: $form);
        $this->checkForErrors($json);
    }

    /**
     * Set the flair on a user in a subreddit.
     *
     * @param string $subredditName Subreddit name (without r/ prefix)
     * @param string $username Username to set flair for
     * @param string|null $flairTemplateId Flair template ID (from getUserFlairs)
     * @param string|null $text Custom flair text (if template allows editing)
     * @param string|null $cssClass CSS class for the flair
     */
    public function setUserFlair(
        string $subredditName,
        string $username,
        ?string $flairTemplateId = null,
        ?string $text = null,
        ?string $cssClass = null
    ): void {
        $form = [
            'api_type' => 'json',
            'name' => $username,
        ];

        if ($flairTemplateId !== null) {
            $form['flair_template_id'] = $flairTemplateId;
        }
        if ($text !== null) {
            $form['text'] = $text;
        }
        if ($cssClass !== null) {
            $form['css_class'] = $cssClass;
        }

        $json = $this->client->request('POST', "/r/{$subredditName}/api/selectflair", form: $form);
        $this->checkForErrors($json);
    }

    /**
     * Remove flair from a user in a subreddit.
     *
     * @param string $subredditName Subreddit name (without r/ prefix)
     * @param string $username Username to remove flair from
     */
    public function removeUserFlair(string $subredditName, string $username): void
    {
        $this->client->request('POST', "/r/{$subredditName}/api/deleteflair", form: [
            'api_type' => 'json',
            'name' => $username,
        ]);
    }

    /**
     * @deprecated Use getLinkFlairs() or getUserFlairs() instead
     * @return array<int, array<string,mixed>>
     */
    public function get(string $subredditName): array
    {
        $json = $this->client->request('GET', "/r/{$subredditName}/api/flairselector.json");
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $f
     */
    private static function mapFlair(array $f): FlairDTO
    {
        return new FlairDTO(
            id: (string) ($f['id'] ?? ''),
            text: (string) ($f['text'] ?? ''),
            textColor: isset($f['text_color']) && is_string($f['text_color']) ? $f['text_color'] : null,
            backgroundColor: isset($f['background_color']) && is_string($f['background_color']) ? $f['background_color'] : null,
            textEditable: (bool) ($f['text_editable'] ?? false),
            type: (string) ($f['type'] ?? 'text'),
            cssClass: isset($f['css_class']) && is_string($f['css_class']) ? $f['css_class'] : null,
        );
    }

    private function checkForErrors(string $json): void
    {
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $errors = $decoded['json']['errors'] ?? [];
        if (!empty($errors)) {
            $errorMsg = is_array($errors[0]) ? implode(': ', $errors[0]) : (string) $errors[0];
            throw new RedditApiException('Reddit API error: ' . $errorMsg, 0, $json);
        }
    }
}
