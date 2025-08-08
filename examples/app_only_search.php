<?php

declare(strict_types=1);

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

$http = Psr18ClientDiscovery::find();
$requestFactory = Psr17FactoryDiscovery::findRequestFactory();
$streamFactory = Psr17FactoryDiscovery::findStreamFactory();

$config = new Config($userAgent);
$auth = new Auth($http, $requestFactory, $streamFactory, $config);

// App-only token for read-only endpoints like search
$accessToken = $auth->appOnly($clientId, $clientSecret, ['read']);

$api = new RedditApiClient($http, $requestFactory, $streamFactory, $config);
$api->withToken($accessToken);

$listing = $api->search()->get('php', ['limit' => 3, 'sort' => 'relevance']);

foreach ($listing->items as $i => $link) {
    echo sprintf("%d. [%s] %s (%s)\n", $i + 1, $link->subreddit, $link->title, $link->permalink);
}

