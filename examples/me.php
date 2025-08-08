<?php

declare(strict_types=1);

use Avansaber\RedditApi\Config\Config;
use Avansaber\RedditApi\Http\RedditApiClient;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;

require __DIR__ . '/../vendor/autoload.php';

$userAgent = getenv('REDDIT_USER_AGENT') ?: 'avansaber-php-reddit-api/1.0; contact you@example.com';
$accessToken = getenv('REDDIT_ACCESS_TOKEN') ?: '';
if ($accessToken === '') {
    fwrite(STDERR, "Set REDDIT_ACCESS_TOKEN with a valid token.\n");
    exit(1);
}

$http = Psr18ClientDiscovery::find();
$psr17 = Psr17FactoryDiscovery::findRequestFactory();
$streamFactory = Psr17FactoryDiscovery::findStreamFactory();
$config = new Config($userAgent);
$api = new RedditApiClient($http, $psr17, $streamFactory, $config);
$api->withToken($accessToken);

$me = $api->me()->get();
echo json_encode([
    'id' => $me->id,
    'name' => $me->name,
    'isEmployee' => $me->isEmployee,
    'isMod' => $me->isMod,
    'createdUtc' => $me->createdUtc,
], JSON_PRETTY_PRINT) . "\n";

