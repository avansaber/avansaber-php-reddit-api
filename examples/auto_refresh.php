<?php

declare(strict_types=1);

/**
 * Example: Automatic token refresh handling.
 *
 * Demonstrates using the AutoRefreshingClient to automatically
 * refresh expired tokens without manual intervention.
 *
 * Usage:
 *   export REDDIT_CLIENT_ID=your_client_id
 *   export REDDIT_CLIENT_SECRET=your_client_secret
 *   export REDDIT_ACCESS_TOKEN=your_access_token
 *   export REDDIT_REFRESH_TOKEN=your_refresh_token
 *   export REDDIT_USER_AGENT="yourapp/1.0 (by /u/yourusername)"
 *   php examples/auto_refresh.php
 */

use Avansaber\RedditApi\Auth\Auth;
use Avansaber\RedditApi\Auth\InMemoryTokenStorage;
use Avansaber\RedditApi\Auth\Token;
use Avansaber\RedditApi\Auth\TokenRefresher;
use Avansaber\RedditApi\Config\Config;
use Avansaber\RedditApi\Http\AutoRefreshingClient;
use Avansaber\RedditApi\Http\RedditApiClient;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;

require __DIR__ . '/../vendor/autoload.php';

$clientId = getenv('REDDIT_CLIENT_ID') ?: '';
$clientSecret = getenv('REDDIT_CLIENT_SECRET') ?: '';
$accessToken = getenv('REDDIT_ACCESS_TOKEN') ?: '';
$refreshToken = getenv('REDDIT_REFRESH_TOKEN') ?: '';
$userAgent = getenv('REDDIT_USER_AGENT') ?: 'avansaber-php-reddit-api/1.0; contact you@example.com';

if ($clientId === '' || $clientSecret === '') {
    fwrite(STDERR, "Set REDDIT_CLIENT_ID and REDDIT_CLIENT_SECRET in your environment.\n");
    exit(1);
}

if ($accessToken === '' || $refreshToken === '') {
    fwrite(STDERR, "Set REDDIT_ACCESS_TOKEN and REDDIT_REFRESH_TOKEN in your environment.\n");
    fwrite(STDERR, "(Obtain these via the PKCE OAuth flow)\n");
    exit(1);
}

$http = Psr18ClientDiscovery::find();
$requestFactory = Psr17FactoryDiscovery::findRequestFactory();
$streamFactory = Psr17FactoryDiscovery::findStreamFactory();

$config = new Config($userAgent);

// Create the Auth instance for refreshing tokens
$auth = new Auth($http, $requestFactory, $streamFactory, $config);

// Create a token storage (in-memory for this example, use PdoSqliteTokenStorage for persistence)
$tokenStorage = new InMemoryTokenStorage();

// Store the initial token
$token = new Token(
    providerUserId: 'demo_user',
    accessToken: $accessToken,
    refreshToken: $refreshToken,
    expiresAtEpoch: time() + 3600, // Assume 1 hour validity
    scopes: ['identity', 'read'],
    ownerUserId: null,
    ownerTenantId: null,
);
$tokenStorage->save($token);

// Create the token refresher
$tokenRefresher = new TokenRefresher($auth, $tokenStorage, $clientId, $clientSecret);

// Create the base API client
$baseClient = new RedditApiClient($http, $requestFactory, $streamFactory, $config);
$baseClient->withToken($accessToken);

// Wrap it with auto-refresh capability
$api = new AutoRefreshingClient($baseClient, $tokenRefresher, $token);

echo "=== Auto-Refresh Demo ===\n\n";
echo "Initial token: " . substr($accessToken, 0, 20) . "...\n";
echo "Refresh token: " . substr($refreshToken, 0, 20) . "...\n\n";

// Make API calls - if token expires, it will be automatically refreshed
try {
    echo "Fetching current user...\n";
    $me = $api->me()->get();
    echo "  Username: {$me->name}\n";
    echo "  ID: {$me->id}\n";
    echo "  Total Karma: {$me->totalKarma}\n\n";

    echo "Fetching karma breakdown...\n";
    $karma = $api->me()->karma();
    echo "  Karma from " . count($karma) . " subreddits\n";
    if (count($karma) > 0) {
        $top = $karma[0];
        echo "  Top: r/{$top->subreddit} - {$top->linkKarma} link / {$top->commentKarma} comment karma\n";
    }

    echo "\nAll operations completed successfully!\n";
    echo "(If token was expired, it was automatically refreshed)\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "\nError: " . $e->getMessage() . "\n");
    exit(1);
}
