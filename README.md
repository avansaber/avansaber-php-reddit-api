avansaber/php-reddit-api

Modern, fluent, framework-agnostic Reddit API client for PHP (PSR-18/PSR-7/PSR-3).

![CI](https://github.com/avansaber/avansaber-php-reddit-api/actions/workflows/ci.yml/badge.svg)
[![Packagist](https://img.shields.io/packagist/v/avansaber/php-reddit-api.svg)](https://packagist.org/packages/avansaber/php-reddit-api)
[![Downloads](https://img.shields.io/packagist/dt/avansaber/php-reddit-api.svg)](https://packagist.org/packages/avansaber/php-reddit-api)

Features
- PSR-18 HTTP client and PSR-7/17 factories (bring your own client)
- Typed DTOs and resources (`me`, `search`, `subreddit`, `user`)
- Write actions (`vote`, `reply`)
- Token storage abstraction and optional SQLite storage
- Auto-refresh tokens on 401
- Retries/backoff for 429/5xx

Requirements
- PHP 8.1+
- Any PSR-18 HTTP client and PSR-7/17 factories (auto-discovered via `php-http/discovery`)

Installation
```bash
composer require avansaber/php-reddit-api
```

To run the examples, install a PSR-18 client implementation (discovery will find it):
```bash
composer require php-http/guzzle7-adapter guzzlehttp/guzzle
```

Getting Reddit API credentials
- Log in to Reddit, open `https://www.reddit.com/prefs/apps`.
- Click “create another app”.
- For app-only reads, choose type “script”. For end-user auth, choose “web app” (Authorization Code) and set a valid redirect URI.
- Fill name and description, then create.
- Copy:
  - Client ID: the short string directly under your app name (next to the app icon). For “personal use script” apps this is a 14‑character string shown under the app name.
  - Client Secret: the value labeled “secret” on the app page (not present for “installed” apps).
- Provide a descriptive User-Agent per Reddit policy, e.g. `yourapp/1.0 (by yourdomain.com; contact you@example.com)`.

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

Authentication
- App-only (client credentials) for read endpoints like search:
  ```php
use Avansaber\RedditApi\Auth\Auth;
use Avansaber\RedditApi\Config\Config;
use Http\Discovery\Psr18ClientDiscovery; use Http\Discovery\Psr17FactoryDiscovery;

$http = Psr18ClientDiscovery::find();
$psr17 = Psr17FactoryDiscovery::findRequestFactory();
$streamFactory = Psr17FactoryDiscovery::findStreamFactory();
$config = new Config('yourapp/1.0 (by yourdomain.com; contact you@example.com)');
$auth = new Auth($http, $psr17, $streamFactory, $config);
$accessToken = $auth->appOnly('CLIENT_ID', 'CLIENT_SECRET', ['read','identity']);
  ```
- Using an existing user token:
  - Set `REDDIT_ACCESS_TOKEN` and run `examples/me.php`.
Authorization Code + PKCE
- Generate PKCE pair and build the authorize URL, then exchange the code on callback:
  ```php
use Avansaber\RedditApi\Auth\Auth;
use Avansaber\RedditApi\Config\Config;
use Http\Discovery\Psr18ClientDiscovery; use Http\Discovery\Psr17FactoryDiscovery;

$http = Psr18ClientDiscovery::find();
$psr17 = Psr17FactoryDiscovery::findRequestFactory();
$streamFactory = Psr17FactoryDiscovery::findStreamFactory();
$config = new Config('yourapp/1.0 (by yourdomain.com; contact you@example.com)');
$auth = new Auth($http, $psr17, $streamFactory, $config);

$pkce = $auth->generatePkcePair(); // ['verifier' => '...', 'challenge' => '...']
$url = $auth->getAuthUrl('CLIENT_ID', 'https://yourapp/callback', ['identity','read','submit'], 'csrf123', $pkce['challenge']);
// Redirect user to $url

// In your callback handler:
$tokens = $auth->getAccessTokenFromCode('CLIENT_ID', null, $_GET['code'], 'https://yourapp/callback', $pkce['verifier']);
// $tokens contains access_token, refresh_token, expires_in, scope
  ```

Temporary manual Authorization Code exchange (for testing)
```bash
# After you obtain ?code=... from the authorize redirect
curl -A "macos:avansaber-php-reddit-api:0.1 (by /u/YourRedditUsername)" \
  -u 'CLIENT_ID:CLIENT_SECRET' \
  -d 'grant_type=authorization_code&code=PASTE_CODE&redirect_uri=http://localhost:8080/callback' \
  https://www.reddit.com/api/v1/access_token

export REDDIT_USER_AGENT="macos:avansaber-php-reddit-api:0.1 (by /u/YourRedditUsername)"
export REDDIT_ACCESS_TOKEN=PASTE_ACCESS_TOKEN
php examples/me.php
```

Scopes
- Reddit uses space-separated scopes when requesting tokens. Common scopes used by this package:

| Scope            | Description                      | Used by                                 |
| ---------------- | -------------------------------- | --------------------------------------- |
| identity         | Verify the current user          | `me()`                                  |
| read             | Read public data                 | `search()`, `subreddit()->about()`, `user()->about()` |
| vote             | Vote on posts and comments       | `links()->upvote()`, `downvote()`, `unvote()` |
| submit           | Submit links or comments         | `links()->reply()`                       |
| privatemessages  | Send/read private messages       | Private messages (planned)               |

Authorization Code + PKCE (placeholder)
- A guided example will be added soon. It will cover:
  - Generating a code verifier/challenge (S256)
  - Building the authorize URL with scopes and state
  - Handling the redirect URI and exchanging the authorization code for tokens
  - Storing tokens (including refresh token) securely and auto-refreshing
  - Example controller/route snippets (and Laravel bridge)
- Until then, you can use app-only auth for read operations, or supply an existing user token via `REDDIT_ACCESS_TOKEN`.

Common usage
- Search posts:
  ```php
$listing = $client->search()->get('php', ['limit' => 5, 'sort' => 'relevance']);
foreach ($listing->items as $post) {
    echo $post->title . "\n";
}
  ```
- Pagination helper example:
  ```php
$first = $client->search()->get('php', ['limit' => 100]);
foreach ($first->iterate(fn($after) => $client->search()->get('php', ['limit' => 100, 'after' => $after])) as $post) {
    // handle $post (Link DTO) across multiple pages
}
  ```
- User history (comments/submitted):
  ```php
$comments = $client->user()->comments('spez', ['limit' => 10]);
$posts = $client->user()->submitted('spez', ['limit' => 10]);
  ```
- Subreddit info: `$sr = $client->subreddit()->about('php');`
- User info: `$u = $client->user()->about('spez');`
- Voting and replying (requires user-context token with proper scopes):
  ```php
$client->links()->upvote('t3_abc123');
$comment = $client->links()->reply('t3_abc123', 'Nice post!');
  ```
- Private messages inbox:
  ```php
$inbox = $client->messages()->inbox(['limit' => 10]);
  ```
- Basic moderation:
  ```php
$client->moderation()->approve('t3_abc123');
$client->moderation()->remove('t3_abc123', true);
  ```

Rate limiting and retries
- Reddit returns `x-ratelimit-remaining`, `x-ratelimit-used`, `x-ratelimit-reset` headers.
- The client retries 429/5xx with exponential backoff and respects `Retry-After` when present.

Error handling
- Methods throw `Avansaber\RedditApi\Exceptions\RedditApiException` on non-2xx.
- You can inspect `getStatusCode()` and `getResponseBody()` for details.

HTTP client setup
- By default we use discovery to find a PSR-18 client and PSR-7/17 factories.
- Alternatively, install and wire your own (e.g., Guzzle + Nyholm PSR-7) and pass them to the constructor.

### Framework integration (Laravel, CodeIgniter, etc.)
- Works in any framework as long as a PSR-18 client and PSR-7/17 factories are available (use discovery):
  ```php
use Avansaber\RedditApi\Config\Config;
use Avansaber\RedditApi\Http\RedditApiClient;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;

$http = Psr18ClientDiscovery::find();
$psr17 = Psr17FactoryDiscovery::findRequestFactory();
$streams = Psr17FactoryDiscovery::findStreamFactory();
$config = new Config(getenv('REDDIT_USER_AGENT'));
$client = new RedditApiClient($http, $psr17, $streams, $config);
  ```

- Laravel (until the dedicated bridge is released)
  - In `App\Providers\AppServiceProvider` → `register()`:
    ```php
use Avansaber\RedditApi\Config\Config;
use Avansaber\RedditApi\Http\RedditApiClient;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;

public function register(): void
{
    $this->app->singleton(RedditApiClient::class, function () {
        $http = Psr18ClientDiscovery::find();
        $psr17 = Psr17FactoryDiscovery::findRequestFactory();
        $streams = Psr17FactoryDiscovery::findStreamFactory();
        $config = new Config(config('services.reddit.user_agent'));
        return new RedditApiClient($http, $psr17, $streams, $config);
    });
}
    ```
  - In `config/services.php`:
    ```php
'reddit' => [
    'client_id' => env('REDDIT_CLIENT_ID'),
    'client_secret' => env('REDDIT_CLIENT_SECRET'),
    'user_agent' => env('REDDIT_USER_AGENT'),
],
    ```
  - Example usage in a controller:
    ```php
public function search(\Avansaber\RedditApi\Http\RedditApiClient $client)
{
    // For app-only reads you can fetch a token via Auth::appOnly and call withToken()
    // $token = ...; $client->withToken($token);
    return response()->json($client->search()->get('php', ['limit' => 5]));
}
    ```
  - For user-context (vote/reply), obtain a user access token (e.g., Socialite Providers: Reddit, or README’s temporary curl step) and call `$client->withToken($userAccessToken)`.

- CodeIgniter 4
  - Create a service in `app/Config/Services.php` that returns `RedditApiClient` using discovery (same as above), then type-hint it in controllers.
  - Provide env keys: `REDDIT_USER_AGENT`, `REDDIT_CLIENT_ID`, `REDDIT_CLIENT_SECRET`.

Examples
- App-only + Search: `examples/app_only_search.php`
- Me endpoint with existing token: `examples/me.php`

Laravel
- See `laravel-plan.md` for the planned bridge package.

Troubleshooting (403 "whoa there, pardner!")
- Reddit may block requests based on IP/UA policies (common with VPN/DC IPs or generic UAs).
- Use a descriptive UA including your Reddit username, e.g. `macos:avansaber-php-reddit-api:0.1 (by /u/YourRedditUsername)`.
- Run from a residential network; avoid VPN/corporate IPs. Add small delays between calls.
- If still blocked, file a ticket with Reddit and include the block code from the response page.

Security notes
- Treat client secrets and access tokens as sensitive. Use environment variables and do not commit them.
- Rotate secrets if they were exposed during testing.

Contributing
- See CONTRIBUTING.md

Security
- See SECURITY.md

License
- MIT

 