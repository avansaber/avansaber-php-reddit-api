avansaber/php-reddit-api

Modern, fluent, framework-agnostic Reddit API client for PHP (PSR-18/PSR-7/PSR-3).

Features
- PSR-18 HTTP client and PSR-7/17 factories (bring your own client)
- Typed DTOs and resources (`me`, `search`, `subreddit`, `user`)
- Write actions (`vote`, `reply`)
- Token storage abstraction and optional SQLite storage
- Auto-refresh tokens on 401
- Retries/backoff for 429/5xx

Installation
```bash
composer require avansaber/php-reddit-api
```

Quickstart
```php
use Avansaber\RedditApi\Config\Config;
use Avansaber\RedditApi\Http\RedditApiClient;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;

$config = new Config('avansaber-php-reddit-api/1.0; contact you@example.com');
$http = Psr18ClientDiscovery::find();
$psr17 = Psr17FactoryDiscovery::findRequestFactory();
$streamFactory = Psr17FactoryDiscovery::findStreamFactory();

$client = new RedditApiClient($http, $psr17, $streamFactory, $config);
$client->withToken('YOUR_ACCESS_TOKEN');
$me = $client->me()->get();
```

Examples
- App-only + Search: `examples/app_only_search.php`
- Me endpoint with existing token: `examples/me.php`

Laravel
- See `laravel-plan.md` for the planned bridge package.

Contributing
- See CONTRIBUTING.md

Security
- See SECURITY.md

License
- MIT

 