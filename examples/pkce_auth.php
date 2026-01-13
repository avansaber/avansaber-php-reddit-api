<?php

declare(strict_types=1);

/**
 * Example: Full PKCE OAuth flow with CSRF state validation.
 *
 * This demonstrates the secure Authorization Code + PKCE flow.
 * In a real application, you would:
 * 1. Store state and verifier in the user's session
 * 2. Redirect to the auth URL
 * 3. Handle the callback and validate state
 * 4. Exchange the code for tokens
 *
 * Usage:
 *   export REDDIT_CLIENT_ID=your_client_id
 *   export REDDIT_USER_AGENT="yourapp/1.0 (by /u/yourusername)"
 *   php examples/pkce_auth.php
 */

use Avansaber\RedditApi\Auth\Auth;
use Avansaber\RedditApi\Config\Config;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;

require __DIR__ . '/../vendor/autoload.php';

$clientId = getenv('REDDIT_CLIENT_ID') ?: '';
$userAgent = getenv('REDDIT_USER_AGENT') ?: 'avansaber-php-reddit-api/1.0; contact you@example.com';
$redirectUri = getenv('REDDIT_REDIRECT_URI') ?: 'http://localhost:8080/callback';

if ($clientId === '') {
    fwrite(STDERR, "Set REDDIT_CLIENT_ID in your environment.\n");
    exit(1);
}

$http = Psr18ClientDiscovery::find();
$requestFactory = Psr17FactoryDiscovery::findRequestFactory();
$streamFactory = Psr17FactoryDiscovery::findStreamFactory();

$config = new Config($userAgent);
$auth = new Auth($http, $requestFactory, $streamFactory, $config);

// Step 1: Generate PKCE pair and CSRF state
$pkce = $auth->generatePkcePair();
$state = Auth::generateState();

echo "=== PKCE OAuth Flow Demo ===\n\n";
echo "Generated PKCE pair:\n";
echo "  Verifier: " . substr($pkce['verifier'], 0, 20) . "...\n";
echo "  Challenge: " . substr($pkce['challenge'], 0, 20) . "...\n";
echo "  State: $state\n\n";

// Step 2: Build the authorization URL
$scopes = ['identity', 'read', 'vote', 'submit'];
$authUrl = $auth->getAuthUrl($clientId, $redirectUri, $scopes, $state, $pkce['challenge']);

echo "Authorization URL (redirect user here):\n";
echo "$authUrl\n\n";

echo "In your session, store:\n";
echo "  \$_SESSION['oauth_state'] = '$state';\n";
echo "  \$_SESSION['oauth_verifier'] = '{$pkce['verifier']}';\n\n";

echo "=== Callback Handler (pseudocode) ===\n\n";
echo <<<'CODE'
// In your callback handler:
try {
    Auth::validateState($_SESSION['oauth_state'], $_GET['state']);
} catch (\InvalidArgumentException $e) {
    die('CSRF validation failed: ' . $e->getMessage());
}

$tokens = $auth->getAccessTokenFromCode(
    $clientId,
    null, // client secret (null for public apps)
    $_GET['code'],
    $redirectUri,
    $_SESSION['oauth_verifier']
);

// $tokens contains:
// - access_token: Use this to make API calls
// - refresh_token: Use this to get new access tokens
// - expires_in: Token lifetime in seconds
// - scope: Granted scopes

$api = new RedditApiClient($http, $requestFactory, $streamFactory, $config);
$api->withToken($tokens['access_token']);
$me = $api->me()->get();
CODE;

echo "\n";
