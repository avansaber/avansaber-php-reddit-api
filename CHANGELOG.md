# Changelog

All notable changes to this project will be documented in this file.

## v0.2.0

### Added
- Authorization Code + PKCE flow for user-context authentication
- `Auth::generateState()` for CSRF-safe state parameter generation
- `Auth::validateState()` for OAuth state validation (prevents CSRF attacks)
- User history endpoints: `user()->comments()`, `user()->submitted()`
- Private messages: `messages()->inbox()`, `sent()`, `unread()`, `compose()`, `markRead()`, `markUnread()`, `delete()`, `blockAuthor()`
- Moderation actions: `moderation()->approve()`, `remove()`, `lock()`, `unlock()`, `sticky()`, `unsticky()`, `distinguish()`, `ignoreReports()`, `markNsfw()`, `markSpoiler()`
- Flair: `flair()->getLinkFlairs()`, `getUserFlairs()`, `setLinkFlair()`, `setUserFlair()`, `removeUserFlair()`
- `Flair` DTO with id, text, colors, cssClass, textEditable
- Value objects: `Fullname`, `SubredditName`, `Username` (with proper validation)
- Enums: `Sort`, `TimeWindow`, `VoteDirection`
- Pagination iterator helper: `Listing::iterate()`
- `RealSleeper` for production rate limit backoff (replaces NoopSleeper as default)
- Configurable OAuth endpoints via `Config::$authBaseUri`
- Link operations: `links()->submitText()`, `submitLink()`, `edit()`, `delete()`, `save()`, `unsave()`, `hide()`, `unhide()`
- Token encryption support via `PdoSqliteTokenStorage` (optional sodium encryption)
- `PdoSqliteTokenStorage::deleteExpiredTokens()` for cleaning up expired tokens
- `PdoSqliteTokenStorage::generateEncryptionKey()` helper for key generation
- `Message` DTO for private messages with full field coverage
- Subreddit listings: `subreddit()->hot()`, `new()`, `top()`, `rising()`, `controversial()`
- Subreddit actions: `subreddit()->subscribe()`, `unsubscribe()`, `rules()`
- Comments resource: `comments()->get()`, `getComment()` for fetching post comments
- PHP 8.4 support in CI matrix
- Composer dependency caching in CI

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
- `Username` validation: 3-20 chars, alphanumeric + hyphen/underscore, cannot start with hyphen/underscore
- `SubredditName` validation: 3-21 chars, alphanumeric + underscore, strips r/ prefix automatically
- `Config` validation: timeout bounds (1-120s), retry bounds (0-10)

### Removed
- Duplicate "Authorization Code + PKCE (placeholder)" section from README

## v0.1.0
- Initial release: core client, app-only auth, resources (me, search, subreddit, user), write actions (vote, reply), token storage, retries/backoff, CI, docs.
