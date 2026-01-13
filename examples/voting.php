<?php

declare(strict_types=1);

/**
 * Example: Voting on posts and comments.
 *
 * Requires a user access token with 'vote' scope.
 *
 * Usage:
 *   export REDDIT_ACCESS_TOKEN=your_user_token
 *   export REDDIT_USER_AGENT="yourapp/1.0 (by /u/yourusername)"
 *   php examples/voting.php t3_abc123
 */

use Avansaber\RedditApi\Config\Config;
use Avansaber\RedditApi\Http\RedditApiClient;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;

require __DIR__ . '/../vendor/autoload.php';

$userAgent = getenv('REDDIT_USER_AGENT') ?: 'avansaber-php-reddit-api/1.0; contact you@example.com';
$accessToken = getenv('REDDIT_ACCESS_TOKEN') ?: '';

if ($accessToken === '') {
    fwrite(STDERR, "Set REDDIT_ACCESS_TOKEN with a valid user token (requires 'vote' scope).\n");
    exit(1);
}

$fullname = $argv[1] ?? '';
if ($fullname === '') {
    fwrite(STDERR, "Usage: php examples/voting.php <fullname>\n");
    fwrite(STDERR, "Example: php examples/voting.php t3_abc123\n");
    fwrite(STDERR, "\nFullname format: t3_xxx for posts, t1_xxx for comments\n");
    exit(1);
}

$http = Psr18ClientDiscovery::find();
$psr17 = Psr17FactoryDiscovery::findRequestFactory();
$streamFactory = Psr17FactoryDiscovery::findStreamFactory();

$config = new Config($userAgent);
$api = new RedditApiClient($http, $psr17, $streamFactory, $config);
$api->withToken($accessToken);

echo "=== Voting Demo ===\n\n";
echo "Target: $fullname\n\n";

// Demonstrate voting actions
try {
    echo "Upvoting... ";
    $api->links()->upvote($fullname);
    echo "OK\n";

    sleep(1); // Be nice to the API

    echo "Removing vote... ";
    $api->links()->unvote($fullname);
    echo "OK\n";

    sleep(1);

    echo "Downvoting... ";
    $api->links()->downvote($fullname);
    echo "OK\n";

    sleep(1);

    echo "Removing vote again... ";
    $api->links()->unvote($fullname);
    echo "OK\n";

    echo "\nAll voting operations completed successfully!\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "\nError: " . $e->getMessage() . "\n");
    exit(1);
}
