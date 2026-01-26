# Core-API TODO

## Testing & Quality Assurance

### High Priority

- [ ] **Test Coverage: API Key Security** - Test bcrypt hashing and rotation
  - [ ] Test API key creation with bcrypt hashing
  - [ ] Test API key authentication
  - [ ] Test key rotation with grace period
  - [ ] Test key revocation
  - [ ] Test scoped key access
  - **Estimated effort:** 3-4 hours

- [ ] **Test Coverage: Webhook System** - Test delivery and signatures
  - [ ] Test webhook endpoint registration
  - [ ] Test HMAC-SHA256 signature generation
  - [ ] Test signature verification
  - [ ] Test webhook delivery retry logic
  - [ ] Test exponential backoff
  - [ ] Test delivery status tracking
  - **Estimated effort:** 4-5 hours

- [ ] **Test Coverage: Rate Limiting** - Test tier-based limits
  - [ ] Test per-tier rate limits
  - [ ] Test rate limit headers
  - [ ] Test quota exceeded responses
  - [ ] Test workspace-scoped limits
  - [ ] Test burst allowance
  - **Estimated effort:** 3-4 hours

- [ ] **Test Coverage: Scope Enforcement** - Test permission system
  - [ ] Test EnforceApiScope middleware
  - [ ] Test wildcard scopes (posts:*, *:read)
  - [ ] Test scope inheritance
  - [ ] Test scope validation errors
  - **Estimated effort:** 3-4 hours

### Medium Priority

- [ ] **Test Coverage: OpenAPI Documentation** - Test spec generation
  - [ ] Test OpenApiBuilder with controller scanning
  - [ ] Test #[ApiParameter] attribute parsing
  - [ ] Test #[ApiResponse] rendering
  - [ ] Test #[ApiSecurity] requirements
  - [ ] Test #[ApiHidden] filtering
  - [ ] Test extension system
  - **Estimated effort:** 4-5 hours

- [ ] **Test Coverage: Usage Alerts** - Test quota monitoring
  - [ ] Test CheckApiUsageAlerts command
  - [ ] Test HighApiUsageNotification delivery
  - [ ] Test usage alert thresholds
  - [ ] Test alert history tracking
  - **Estimated effort:** 2-3 hours

### Low Priority

- [ ] **Test Coverage: Webhook Payload Validation** - Test request validation
  - [ ] Test payload size limits
  - [ ] Test content-type validation
  - [ ] Test malformed JSON handling
  - **Estimated effort:** 2-3 hours

## Features & Enhancements

### High Priority

- [ ] **Feature: API Versioning** - Support multiple API versions
  - [ ] Implement version routing (v1, v2)
  - [ ] Add version deprecation warnings
  - [ ] Support version-specific transformers
  - [ ] Document migration between versions
  - [ ] Test backward compatibility
  - **Estimated effort:** 6-8 hours
  - **Files:** `src/Mod/Api/Versioning/`

- [ ] **Feature: GraphQL API** - Alternative to REST
  - [ ] Implement GraphQL schema generation
  - [ ] Add query resolver system
  - [ ] Support mutations
  - [ ] Add introspection
  - [ ] Test complex nested queries
  - **Estimated effort:** 12-16 hours
  - **Files:** `src/Mod/Api/GraphQL/`

- [ ] **Feature: Batch Operations** - Bulk API requests
  - [ ] Support batched requests
  - [ ] Implement atomic batch transactions
  - [ ] Add batch size limits
  - [ ] Test error handling in batches
  - **Estimated effort:** 4-6 hours
  - **Files:** `src/Mod/Api/Batch/`

### Medium Priority

- [ ] **Enhancement: Webhook Transformers** - Custom payload formatting
  - [ ] Create transformer interface
  - [ ] Support per-endpoint transformers
  - [ ] Add JSON-LD format support
  - [ ] Test with complex data structures
  - **Estimated effort:** 3-4 hours
  - **Files:** `src/Mod/Api/Webhooks/Transformers/`

- [ ] **Enhancement: API Analytics** - Detailed usage metrics
  - [ ] Track API calls per endpoint
  - [ ] Monitor response times
  - [ ] Track error rates
  - [ ] Create admin dashboard
  - [ ] Add export to CSV
  - **Estimated effort:** 5-6 hours
  - **Files:** `src/Mod/Api/Analytics/`

- [ ] **Enhancement: Request Throttling Strategies** - Advanced rate limiting
  - [ ] Implement sliding window algorithm
  - [ ] Add burst allowance
  - [ ] Support custom throttle strategies
  - [ ] Add per-endpoint rate limits
  - **Estimated effort:** 4-5 hours
  - **Files:** `src/Mod/Api/RateLimit/Strategies/`

### Low Priority

- [ ] **Enhancement: API Client SDK Generator** - Auto-generate SDKs
  - [ ] Generate PHP SDK from OpenAPI
  - [ ] Generate JavaScript SDK
  - [ ] Generate Python SDK
  - [ ] Add usage examples
  - **Estimated effort:** 8-10 hours
  - **Files:** `src/Mod/Api/Sdk/`

- [ ] **Enhancement: Webhook Retry Dashboard** - Visual delivery monitoring
  - [ ] Create delivery status dashboard
  - [ ] Add manual retry button
  - [ ] Show delivery timeline
  - [ ] Export delivery logs
  - **Estimated effort:** 3-4 hours
  - **Files:** `src/Website/Api/Components/`

## Security

### High Priority

- [ ] **Security: API Key IP Whitelisting** - Restrict key usage
  - [ ] Add allowed_ips column to api_keys
  - [ ] Validate request IP against whitelist
  - [ ] Test with IPv4 and IPv6
  - [ ] Add CIDR notation support
  - **Estimated effort:** 3-4 hours

- [ ] **Security: Request Signing** - Prevent replay attacks
  - [ ] Implement timestamp validation
  - [ ] Add nonce tracking
  - [ ] Support custom signing algorithms
  - [ ] Test with clock skew
  - **Estimated effort:** 4-5 hours

### Medium Priority

- [ ] **Security: Webhook Mutual TLS** - Secure webhook delivery
  - [ ] Add client certificate support
  - [ ] Implement certificate validation
  - [ ] Test with self-signed certs
  - **Estimated effort:** 4-5 hours

- [ ] **Audit: API Permission Model** - Review scope granularity
  - [ ] Audit all API scopes
  - [ ] Ensure least-privilege defaults
  - [ ] Document scope requirements
  - [ ] Test scope escalation attempts
  - **Estimated effort:** 3-4 hours

## Documentation

- [x] **Guide: Building REST APIs** - Complete tutorial
  - [x] Document resource creation
  - [x] Show pagination best practices
  - [x] Explain filtering and sorting
  - [x] Add authentication examples
  - **Completed:** January 2026
  - **File:** `docs/packages/api/building-rest-apis.md`

- [x] **Guide: Webhook Integration** - For API consumers
  - [x] Document signature verification
  - [x] Show retry handling
  - [x] Explain event types
  - [x] Add code examples (PHP, JS, Python)
  - **Completed:** January 2026
  - **File:** `docs/packages/api/webhook-integration.md`

- [x] **API Reference: All Endpoints** - Complete OpenAPI spec
  - [x] Document all request parameters
  - [x] Add response examples
  - [x] Show error responses
  - [x] Include authentication notes
  - **Completed:** January 2026
  - **File:** `docs/packages/api/endpoints-reference.md`

## Code Quality

- [ ] **Refactor: Extract Rate Limiter** - Reusable rate limiting
  - [ ] Create standalone RateLimiter service
  - [ ] Support multiple backends (Redis, DB, memory)
  - [ ] Add configurable strategies
  - [ ] Test with high concurrency
  - **Estimated effort:** 3-4 hours

- [ ] **Refactor: Webhook Queue Priority** - Prioritize critical webhooks
  - [ ] Add priority field to webhooks
  - [ ] Implement priority queue
  - [ ] Test delivery order
  - **Estimated effort:** 2-3 hours

- [ ] **PHPStan: Fix Level 5 Errors** - Improve type safety
  - [ ] Fix array shape types in resources
  - [ ] Add missing return types
  - [ ] Fix property type declarations
  - **Estimated effort:** 2-3 hours

## Performance

- [ ] **Optimization: Response Caching** - Cache GET requests
  - [ ] Implement HTTP cache headers
  - [ ] Add ETag support
  - [ ] Support cache invalidation
  - [ ] Test with CDN
  - **Estimated effort:** 3-4 hours

- [ ] **Optimization: Database Query Reduction** - Eager load relationships
  - [ ] Audit N+1 queries in resources
  - [ ] Add eager loading
  - [ ] Benchmark before/after
  - **Estimated effort:** 2-3 hours

---

## Completed (January 2026)

- [x] **API Key Hashing** - Bcrypt hashing for all API keys
- [x] **Webhook Signatures** - HMAC-SHA256 signature verification
- [x] **Scope System** - Fine-grained API permissions
- [x] **Rate Limiting** - Tier-based rate limits with usage alerts
- [x] **OpenAPI Documentation** - Auto-generated API docs with Swagger/Scalar/ReDoc
- [x] **Documentation** - Complete API package documentation

*See `changelog/2026/jan/` for completed features.*
