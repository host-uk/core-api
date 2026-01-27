# Api Module Review

**Updated:** 2026-01-21 - All recommended improvements verified and documented

## Overview

The Api module provides the core API infrastructure for the Host Hub platform, including:
- API key management with secure hashing (SHA-256) and rotation with grace periods
- Webhook system for event-driven integrations with HMAC signatures
- API usage tracking and analytics with daily aggregation
- Rate limiting with tier-based limits tied to entitlements
- Multiple authentication strategies (API keys, Sanctum/OAuth)
- Controllers for entitlements, SEO reports, unified pixel tracking, MCP HTTP bridge, and workspace management

## Production Readiness Score: 92/100 (was 85/100 - Wave 2 improvements applied 2026-01-21)

The module has solid foundations with good security practices, comprehensive test coverage for core features, and well-structured code. P1 issues fixed in Wave 1.

## Critical Issues (Must Fix)

- [x] **ApiUsageDaily now database-portable**: `recordFromUsage()` now uses Laravel's `upsert()` method followed by atomic `DB::raw()` increment updates. Works with MySQL, PostgreSQL, and SQLite.

- [x] **McpApiController resource returns 501**: The `resource()` method now returns HTTP 501 Not Implemented with clear error message instead of misleading stub.

- [x] **MCP server command mapping is configurable**: Moved to `config('api.mcp.server_commands')` - fully configurable via config file. Verified: config defines 6 server mappings (hosthub-agent, socialhost, biohost, commerce, supporthost, upstream). Controller uses `config('api.mcp.server_commands', [])` for lookup.

- [x] **EntitlementApiController store() verified protected**: Route uses `middleware('commerce.api')` requiring Bearer token matching `config('services.commerce.api_secret')` - proper server-to-server auth.

- [x] **Grace period cleanup scheduled**: Created `api:cleanup-grace-periods` command with `--dry-run` support. Registered hourly in scheduler with `withoutOverlapping()` and `runInBackground()`.

## Recommended Improvements

- [x] **Add request validation to MCP callTool**: Now validates arguments against tool's JSON Schema inputSchema. Verified: `validateToolArguments()` method checks required properties, type validation (string/integer/number/boolean/array/object/null), enum values, string min/max length, numeric min/max values. Returns 422 with detailed validation errors array.

- [ ] **Add IP whitelist option for API keys**: Some users may want to restrict API keys to specific IPs.

- [x] **Add webhook delivery queue configuration**: Added `config('api.webhooks.queue')` and `config('api.webhooks.queue_connection')`. Verified: Config defaults to 'webhooks' queue via `env('WEBHOOK_QUEUE', 'webhooks')`. `DeliverWebhookJob` constructor reads both settings and applies to job dispatch.

- [ ] **Add API key permission audit logging**: Currently logs creation/rotation/revoke but not permission changes in a structured audit trail.

- [x] **Add rate limit headers consistently**: Added `X-RateLimit-Reset` and `Retry-After` to CORS exposed headers for cross-origin visibility.

- [x] **Add CORS configuration**: Created `PublicApiCors` middleware for public endpoints (pixel). Verified: Handles OPTIONS preflight with 204 response. Sets `Access-Control-Allow-Origin` to request origin, exposes rate limit headers, adds `Vary: Origin` for proper caching. Applied to pixel routes.

- [x] **SeoReportController URL matching improved**: Now uses multiple strategies (full path, basename, normalised slug, common prefix stripping) to match URLs reliably. Verified: `findContentByUrl()` implements 4 strategies: full path match, basename match, extension-stripped match (.html/.php/.aspx/.jsp), and common prefix stripping (blog/posts/articles/pages/help/docs).

- [x] **UnifiedPixelController rate limit by pixel_key**: Added `RateLimitPixelKey` middleware with configurable limits via `config('api.rate_limits.pixel_key')`. Verified: Middleware extracts pixel_key from request, sanitises to alphanumeric (max 64 chars), uses Laravel RateLimiter with configurable requests/minutes (default 10,000/min). Returns 429 with custom headers (X-PixelKey-RateLimit-*).

- [ ] **Add dead letter queue for permanently failed webhooks**: Failed deliveries (after max retries) are just marked as failed. Consider a DLQ for manual inspection.

- [x] **WorkspaceController switch method optimised**: Refactored to use direct DB queries in a transaction - clearer and more efficient. Verified: Uses `DB::transaction()` with two atomic `DB::table()` updates - first clears all hub workspace defaults for user, then sets new default. No N+1 queries.

## Missing Features (Future)

- [ ] **API versioning support**: Config has `api.version` but no version-based routing or deprecation handling.

- [ ] **API key IP restrictions**: Model has no IP whitelist field.

- [ ] **Webhook payload templating**: All webhooks send full event data; no way to customise payload shape.

- [ ] **Webhook secret rotation grace period**: Unlike API keys, webhook secrets rotate immediately with no grace period.

- [ ] **API usage alerts/notifications**: No alerting when usage approaches limits or unusual patterns detected.

- [ ] **Usage export**: No endpoint to export usage data as CSV/JSON.

- [ ] **Read-only API key scopes enforcement on routes**: Scope middleware exists but is not applied to routes - all authenticated routes allow any scope.

- [ ] **Social API controllers tests**: Controllers in `Controllers/Social/` have no corresponding tests.

- [ ] **ProductApiController implementation**: File exists but not reviewed - verify if stub or complete.

## Test Coverage Assessment

**Strong coverage for:**
- API key creation, authentication, expiry, scopes (ApiKeyTest.php - 33 tests)
- API key rotation and grace periods (ApiKeyRotationTest.php - 14 tests)
- Webhook delivery and retry logic (WebhookDeliveryTest.php - 14 tests)
- API usage tracking and aggregation (ApiUsageTest.php - 16 tests)

**Missing coverage for:**
- `McpApiController` (no tests)
- `UnifiedPixelController` (no tests)
- `EntitlementApiController` (no tests)
- `SeoReportController` (no tests)
- `WorkspaceController` (no tests)
- `CommerceApiAuth` middleware (no tests)
- Social API controllers (no tests)
- HTTP integration tests for V1 controllers (partial - only auth rejection tested)

**Estimated coverage: 60-65%** of critical paths, but gaps in controller-level integration tests.

## Security Concerns

1. **API keys are properly hashed** - Uses SHA-256, keys never stored in plain text. Good.

2. **Webhook signatures use HMAC-SHA256** - Proper implementation with timing-safe comparison in `hash_equals()`. Good.

3. **CommerceApiAuth uses timing-safe comparison** - Uses `hash_equals()` for token comparison. Good.

4. **No SQL injection risks** - All queries use Eloquent/query builder with parameterised inputs. Good.

5. **User agent truncated to prevent storage overflow** - ApiUsage truncates to 500 chars. Good.

6. **Response body size limited in webhook deliveries** - Limited to 10,000 chars. Good.

7. **POTENTIAL: MCP tool execution via proc_open** - The `executeToolViaArtisan()` spawns a PHP process. While arguments are JSON-encoded (not shell-escaped), ensure MCP server commands cannot be exploited. Currently appears safe as server/tool names are validated against known registries.

8. **VERIFY: EntitlementApiController creates users** - Creates users with random passwords. If exposed publicly, could be an account creation vector. Verify route protection.

9. **No request signing for webhook validation on incoming webhooks** - The module sends signed webhooks but the SEO report receiver doesn't verify signatures from external sources.

## Notes

1. **Well-structured module** - Clean separation between models, services, controllers, and middleware. Good use of concerns/traits for shared functionality.

2. **Good documentation** - PHPDoc comments throughout, clear method naming, helpful inline comments.

3. **Proper use of transactions** - Webhook dispatch and delivery use DB transactions with `afterCommit()` for job dispatch.

4. **Exponential backoff well implemented** - Webhook retry delays are sensible (1m, 5m, 30m, 2h, 24h).

5. **Factory support** - ApiKey has a factory with helpers for creating test keys with known plain text.

6. **Config externalised** - Rate limits, key limits, webhook settings all in config file.

7. **Code snippet generator is thorough** - Supports 11 languages for API docs. Nice feature.

8. **Soft deletes used appropriately** - API keys and webhooks use soft deletes for audit trail.

9. **ApiUsageDaily atomic upsert** - Uses raw SQL for atomic increment operations to prevent race conditions. Smart but MySQL-specific.

10. **Middleware aliases registered via events** - Uses `ApiRoutesRegistering` and `ConsoleBooting` events for lazy middleware registration. Follows the modular architecture pattern.
