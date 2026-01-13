# Changelog

All notable changes to this project will be documented in this file.

## v0.2.0

### Added
- Authorization Code + PKCE flow for user-context authentication
- `Auth::generateState()` for CSRF-safe state parameter generation
- `Auth::validateState()` for OAuth state validation (prevents CSRF attacks)
- User history endpoints: `user()->comments()`, `user()->submitted()`
- Private messages inbox: `messages()->inbox()`
- Moderation actions: `moderation()->approve()`, `moderation()->remove()`
- Flair retrieval: `flair()->get()`
- Value objects: `Fullname`, `SubredditName`, `Username`
- Enums: `Sort`, `TimeWindow`, `VoteDirection`
- Pagination iterator helper: `Listing::iterate()`
- `RealSleeper` for production rate limit backoff (replaces NoopSleeper as default)
- Configurable OAuth endpoints via `Config::$authBaseUri`

### Fixed
- **Security**: OAuth flow now includes state parameter helpers for CSRF protection
- **Security**: TokenRefresher now throws clear exception when refresh token is missing (instead of sending empty string)
- **Security**: Links::reply() now validates response structure and throws on invalid/error responses
- Fullname validation regex now includes t7 (modqueue) and t8 (modmail) types
- Rate limit backoff now actually sleeps in production (was using NoopSleeper by default)
- Removed outdated "not yet surfaced" comment about rate limits (they are surfaced via `getLastRateLimitInfo()`)

### Changed
- Sleeper is now configurable via constructor; defaults to `RealSleeper` in production
- OAuth endpoints are now configurable for testing (via `Config::$authBaseUri`)

### Removed
- Duplicate "Authorization Code + PKCE (placeholder)" section from README

## v0.1.0
- Initial release: core client, app-only auth, resources (me, search, subreddit, user), write actions (vote, reply), token storage, retries/backoff, CI, docs.
