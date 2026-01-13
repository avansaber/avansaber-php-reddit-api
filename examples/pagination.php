<?php

declare(strict_types=1);

/**
 * Example: Pagination through search results.
 *
 * Demonstrates using the Listing::iterate() helper to automatically
 * paginate through multiple pages of results.
 *
 * Usage:
 *   export REDDIT_CLIENT_ID=your_client_id
 *   export REDDIT_CLIENT_SECRET=your_client_secret
 *   export REDDIT_USER_AGENT="yourapp/1.0 (by /u/yourusername)"
 *   php examples/pagination.php "search query"
 */

use Avansaber\RedditApi\Auth\Auth;
use Avansaber\RedditApi\Config\Config;
use Avansaber\RedditApi\Http\RedditApiClient;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;

require __DIR__ . '/../vendor/autoload.php';

$clientId = getenv('REDDIT_CLIENT_ID') ?: '';
$clientSecret = getenv('REDDIT_CLIENT_SECRET') ?: '';
$userAgent = getenv('REDDIT_USER_AGENT') ?: 'avansaber-php-reddit-api/1.0; contact you@example.com';

if ($clientId === '' || $clientSecret === '') {
    fwrite(STDERR, "Set REDDIT_CLIENT_ID and REDDIT_CLIENT_SECRET in your environment.\n");
    exit(1);
}

$query = $argv[1] ?? 'php';
$maxResults = (int) ($argv[2] ?? 50);

$http = Psr18ClientDiscovery::find();
$requestFactory = Psr17FactoryDiscovery::findRequestFactory();
$streamFactory = Psr17FactoryDiscovery::findStreamFactory();

$config = new Config($userAgent);
$auth = new Auth($http, $requestFactory, $streamFactory, $config);

// Get app-only token for searching
$accessToken = $auth->appOnly($clientId, $clientSecret, ['read']);

$api = new RedditApiClient($http, $requestFactory, $streamFactory, $config);
$api->withToken($accessToken);

echo "=== Pagination Demo ===\n\n";
echo "Query: '$query'\n";
echo "Max results: $maxResults\n\n";

$count = 0;
$pageSize = 25;

// Fetch the first page
$listing = $api->search()->get($query, ['limit' => $pageSize, 'sort' => 'relevance']);

// Use the iterate() helper to automatically paginate
foreach ($listing->iterate(fn($after) => $api->search()->get($query, ['limit' => $pageSize, 'after' => $after])) as $post) {
    $count++;
    echo sprintf(
        "%3d. [r/%s] %s\n     %s\n",
        $count,
        $post->subreddit,
        substr($post->title, 0, 60) . (strlen($post->title) > 60 ? '...' : ''),
        'https://reddit.com' . $post->permalink
    );

    if ($count >= $maxResults) {
        echo "\n(Reached max results limit)\n";
        break;
    }
}

echo "\nTotal results fetched: $count\n";
