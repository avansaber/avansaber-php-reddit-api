# Changelog

All notable changes to this project will be documented in this file.

## v0.2.0

### Added
- Authorization Code + PKCE flow for user-context authentication
- `Auth::generateState()` for CSRF-safe state parameter generation
- `Auth::validateState()` for OAuth state validation (prevents CSRF attacks)
- User history endpoints: `user()->comments()`, `user()->submitted()`
- Private messages: `messages()->inbox()`, `sent()`, `unread()`, `compose()`, `markRead()`, `markUnread()`, `delete()`, `blockAuthor()`
- Moderation actions: `moderation()->approve()`, `moderation()->remove()`
- Flair retrieval: `flair()->get()`
- Value objects: `Fullname`, `SubredditName`, `Username`
- Enums: `Sort`, `TimeWindow`, `VoteDirection`
- Pagination iterator helper: `Listing::iterate()`
- `RealSleeper` for production rate limit backoff (replaces NoopSleeper as default)
- Configurable OAuth endpoints via `Config::$authBaseUri`
- Link operations: `links()->submitText()`, `submitLink()`, `edit()`, `delete()`, `save()`, `unsave()`, `hide()`, `unhide()`
- Token encryption support via `PdoSqliteTokenStorage` (optional sodium encryption)
- `PdoSqliteTokenStorage::deleteExpiredTokens()` for cleaning up expired tokens
- `PdoSqliteTokenStorage::generateEncryptionKey()` helper for key generation
- `Message` DTO for private messages with full field coverage

### Fixed
- **Security**: OAuth flow now includes state parameter helpers for CSRF protection
- **Security**: TokenRefresher now throws clear exception when refresh token is missing (instead of sending empty string)
- **Security**: Links::reply() now validates response structure and throws on invalid/error responses
- Fullname validation regex now includes t7 (modqueue) and t8 (modmail) types
- Rate limit backoff now actually sleeps in production (was using NoopSleeper by default)
- Removed outdated "not yet surfaced" comment about rate limits (they are surfaced via `getLastRateLimitInfo()`)
- Token storage race condition fixed using proper UPSERT (ON CONFLICT DO UPDATE)

### Changed
- Sleeper is now configurable via constructor; defaults to `RealSleeper` in production
- OAuth endpoints are now configurable for testing (via `Config::$authBaseUri`)
- Expanded `Link` DTO with 25 fields (added `createdUtc`, `isSelf`, `selftext`, `numComments`, `ups`, `downs`, `edited`, `spoiler`, `locked`, `archived`, `stickied`, `linkFlairText`, `authorFlairText`, `thumbnail`, `domain`)
- Expanded `Comment` DTO with 20 fields (added `createdUtc`, `edited`, `parentId`, `subreddit`, `subredditId`, `linkId`, `isSubmitter`, `stickied`, `scoreHidden`, `locked`, `authorFlairText`)
- Expanded `User` DTO with 14 fields (added `linkKarma`, `commentKarma`, `totalKarma`, `isGold`, `hasVerifiedEmail`, `iconImg`, `over18`, `isSuspended`)
- Expanded `Subreddit` DTO with 18 fields (added `description`, `createdUtc`, `subredditType`, `quarantine`, `bannerImg`, `iconImg`, `headerImg`, `primaryColor`, `keyColor`)

### Removed
- Duplicate "Authorization Code + PKCE (placeholder)" section from README

## v0.1.0
- Initial release: core client, app-only auth, resources (me, search, subreddit, user), write actions (vote, reply), token storage, retries/backoff, CI, docs.
