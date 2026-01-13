<?php

declare(strict_types=1);

/**
 * Example: Moderation actions.
 *
 * Demonstrates moderator actions like approve, remove, lock, sticky, etc.
 * Requires a user access token with 'modposts' scope and moderator
 * privileges in the target subreddit.
 *
 * Usage:
 *   export REDDIT_ACCESS_TOKEN=your_moderator_token
 *   export REDDIT_USER_AGENT="yourapp/1.0 (by /u/yourusername)"
 *   php examples/moderation.php <action> <fullname>
 *
 * Actions: approve, remove, lock, unlock, sticky, unsticky, nsfw, unnsfw, spoiler, unspoiler
 */

use Avansaber\RedditApi\Config\Config;
use Avansaber\RedditApi\Http\RedditApiClient;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;

require __DIR__ . '/../vendor/autoload.php';

$userAgent = getenv('REDDIT_USER_AGENT') ?: 'avansaber-php-reddit-api/1.0; contact you@example.com';
$accessToken = getenv('REDDIT_ACCESS_TOKEN') ?: '';

if ($accessToken === '') {
    fwrite(STDERR, "Set REDDIT_ACCESS_TOKEN with a valid moderator token.\n");
    exit(1);
}

$action = $argv[1] ?? '';
$fullname = $argv[2] ?? '';

if ($action === '' || $fullname === '') {
    fwrite(STDERR, "Usage: php examples/moderation.php <action> <fullname>\n\n");
    fwrite(STDERR, "Actions:\n");
    fwrite(STDERR, "  approve    - Approve a post or comment\n");
    fwrite(STDERR, "  remove     - Remove a post or comment\n");
    fwrite(STDERR, "  spam       - Remove and mark as spam\n");
    fwrite(STDERR, "  lock       - Lock a post or comment\n");
    fwrite(STDERR, "  unlock     - Unlock a post or comment\n");
    fwrite(STDERR, "  sticky     - Sticky a post (pin to top)\n");
    fwrite(STDERR, "  unsticky   - Remove sticky from a post\n");
    fwrite(STDERR, "  nsfw       - Mark as NSFW\n");
    fwrite(STDERR, "  unnsfw     - Remove NSFW marking\n");
    fwrite(STDERR, "  spoiler    - Mark as spoiler\n");
    fwrite(STDERR, "  unspoiler  - Remove spoiler marking\n");
    fwrite(STDERR, "  distinguish - Distinguish as moderator\n");
    fwrite(STDERR, "\nExample: php examples/moderation.php approve t3_abc123\n");
    exit(1);
}

$http = Psr18ClientDiscovery::find();
$psr17 = Psr17FactoryDiscovery::findRequestFactory();
$streamFactory = Psr17FactoryDiscovery::findStreamFactory();

$config = new Config($userAgent);
$api = new RedditApiClient($http, $psr17, $streamFactory, $config);
$api->withToken($accessToken);

echo "=== Moderation Demo ===\n\n";
echo "Action: $action\n";
echo "Target: $fullname\n\n";

try {
    switch ($action) {
        case 'approve':
            $api->moderation()->approve($fullname);
            echo "Approved successfully.\n";
            break;

        case 'remove':
            $api->moderation()->remove($fullname);
            echo "Removed successfully.\n";
            break;

        case 'spam':
            $api->moderation()->remove($fullname, spam: true);
            echo "Removed as spam successfully.\n";
            break;

        case 'lock':
            $api->moderation()->lock($fullname);
            echo "Locked successfully.\n";
            break;

        case 'unlock':
            $api->moderation()->unlock($fullname);
            echo "Unlocked successfully.\n";
            break;

        case 'sticky':
            $api->moderation()->sticky($fullname);
            echo "Stickied successfully.\n";
            break;

        case 'unsticky':
            $api->moderation()->unsticky($fullname);
            echo "Unstickied successfully.\n";
            break;

        case 'nsfw':
            $api->moderation()->markNsfw($fullname);
            echo "Marked as NSFW successfully.\n";
            break;

        case 'unnsfw':
            $api->moderation()->unmarkNsfw($fullname);
            echo "NSFW marking removed successfully.\n";
            break;

        case 'spoiler':
            $api->moderation()->markSpoiler($fullname);
            echo "Marked as spoiler successfully.\n";
            break;

        case 'unspoiler':
            $api->moderation()->unmarkSpoiler($fullname);
            echo "Spoiler marking removed successfully.\n";
            break;

        case 'distinguish':
            $api->moderation()->distinguish($fullname, 'yes');
            echo "Distinguished as moderator successfully.\n";
            break;

        default:
            fwrite(STDERR, "Unknown action: $action\n");
            exit(1);
    }
} catch (\Throwable $e) {
    fwrite(STDERR, "\nError: " . $e->getMessage() . "\n");
    exit(1);
}
