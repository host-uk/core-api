# Core-API - January 2026

## Features Implemented

### Webhook Signing (Outbound)

HMAC-SHA256 signatures with timestamp for replay attack protection.

**Files:**
- `Services/WebhookSignature.php` - Sign/verify service
- `Models/WebhookEndpoint.php` - Signature methods
- `Models/WebhookDelivery.php` - Headers in payload

**Headers:**
| Header | Description |
|--------|-------------|
| `X-Webhook-Signature` | HMAC-SHA256 (64 hex chars) |
| `X-Webhook-Timestamp` | Unix timestamp |
| `X-Webhook-Event` | Event type |
| `X-Webhook-Id` | Unique delivery ID |

**Verification:**
```php
$signature = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
hash_equals($signature, $headerSignature);
```

---

### API Key Security

Secure bcrypt hashing with backward compatibility for legacy SHA-256 keys.

**Files:**
- `Models/ApiKey.php` - Secure hashing, rotation, grace periods
- `Migrations/2026_01_27_*` - Added hash_algorithm column

**Features:**
- New keys use `Hash::make()` (bcrypt)
- Legacy keys continue working
- Key rotation with grace periods
- Scopes: `legacyHash()`, `secureHash()`, `inGracePeriod()`

---

### Rate Limiting

Granular rate limiting with sliding window algorithm.

**Files:**
- `RateLimit/RateLimitService.php` - Sliding window service
- `RateLimit/RateLimitResult.php` - Result DTO
- `RateLimit/RateLimit.php` - PHP 8 attribute
- `Middleware/RateLimitApi.php` - Enhanced middleware
- `Exceptions/RateLimitExceededException.php`

**Features:**
- Per-endpoint limits via `#[RateLimit]` attribute or config
- Per-workspace isolation
- Tier-based limits (free/starter/pro/agency/enterprise)
- Burst allowance (e.g., 20% over limit)
- Headers: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`

**Usage:**
```php
#[RateLimit(limit: 100, window: 60, burst: 1.2)]
public function index() { ... }
```

---

### OpenAPI/Swagger Documentation

Auto-generated API documentation with multiple UI options.

**Files:**
- `Documentation/OpenApiBuilder.php` - Spec generator
- `Documentation/DocumentationController.php` - Routes
- `Documentation/Attributes/` - ApiTag, ApiResponse, ApiSecurity, ApiParameter, ApiHidden
- `Documentation/Extensions/` - WorkspaceHeader, RateLimit, ApiKeyAuth
- `Documentation/Views/` - Swagger, Scalar, ReDoc

**Routes:**
| Route | Description |
|-------|-------------|
| `GET /api/docs` | Default UI (Scalar) |
| `GET /api/docs/swagger` | Swagger UI |
| `GET /api/docs/scalar` | Scalar API Reference |
| `GET /api/docs/redoc` | ReDoc |
| `GET /api/docs/openapi.json` | OpenAPI spec (JSON) |
| `GET /api/docs/openapi.yaml` | OpenAPI spec (YAML) |

**Usage:**
```php
#[ApiTag('Users')]
#[ApiResponse(200, UserResource::class)]
#[ApiParameter('page', 'query', 'integer')]
public function index() { ... }
```

**Config:** `API_DOCS_ENABLED`, `API_DOCS_TITLE`, `API_DOCS_REQUIRE_AUTH`

---

### Documentation Genericization

Removed vendor-specific branding from API documentation.

**Files:**
- `Website/Api/View/Blade/guides/authentication.blade.php`
- `Website/Api/View/Blade/guides/errors.blade.php`
- `Website/Api/View/Blade/guides/index.blade.php`
- `Website/Api/View/Blade/guides/qrcodes.blade.php`
- `Website/Api/View/Blade/guides/quickstart.blade.php`

**Changes:**
- Replaced "Host UK API" with generic "API"
- Removed specific domain references (lt.hn)
- Replaced sign-up URLs with generic account requirements
- Made example URLs vendor-neutral

**Impact:** Framework documentation is now vendor-agnostic and suitable for open-source distribution.
