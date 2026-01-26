# Core API Package

REST API infrastructure with OpenAPI documentation, rate limiting, webhook signing, and secure API key management.

## Installation

```bash
composer require host-uk/core-api
```

## Features

### OpenAPI/Swagger Documentation
Auto-generated API documentation with multiple UI options:

```php
use Core\Mod\Api\Documentation\Attributes\{ApiTag, ApiResponse};

#[ApiTag('Products')]
#[ApiResponse(200, ProductResource::class)]
class ProductController extends Controller
{
    public function index()
    {
        return ProductResource::collection(Product::paginate());
    }
}
```

**Access documentation:**
- `GET /api/docs` - Scalar UI (default)
- `GET /api/docs/swagger` - Swagger UI
- `GET /api/docs/redoc` - ReDoc
- `GET /api/docs/openapi.json` - OpenAPI spec

### Secure API Keys
Bcrypt hashing with backward compatibility:

```php
use Core\Mod\Api\Models\ApiKey;

$key = ApiKey::create([
    'name' => 'Production API',
    'workspace_id' => $workspace->id,
    'scopes' => ['read', 'write'],
]);

// Returns the plain key (shown only once)
$plainKey = $key->getPlainKey();
```

**Features:**
- Bcrypt hashing for new keys
- Legacy SHA-256 support
- Key rotation with grace periods
- Scope-based permissions

### Rate Limiting
Granular rate limiting per endpoint:

```php
use Core\Mod\Api\RateLimit\RateLimit;

#[RateLimit(limit: 100, window: 60, burst: 1.2)]
class ProductController extends Controller
{
    // Limited to 100 requests per 60 seconds
    // With 20% burst allowance
}
```

**Features:**
- Per-endpoint limits
- Workspace isolation
- Tier-based limits
- Standard headers: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`

### Webhook Signing
HMAC-SHA256 signatures for outbound webhooks:

```php
use Core\Mod\Api\Models\WebhookEndpoint;

$endpoint = WebhookEndpoint::create([
    'url' => 'https://example.com/webhooks',
    'events' => ['order.created', 'order.updated'],
    'secret' => WebhookEndpoint::generateSecret(),
]);
```

**Verification:**
```php
$signature = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
hash_equals($signature, $request->header('X-Webhook-Signature'));
```

### Scope Enforcement
Fine-grained API permissions:

```php
use Core\Mod\Api\Middleware\EnforceApiScope;

Route::middleware(['api', EnforceApiScope::class.':write'])
    ->post('/products', [ProductController::class, 'store']);
```

## Configuration

```php
// config/api.php (after php artisan vendor:publish --tag=api-config)

return [
    'rate_limits' => [
        'default' => 60,
        'tiers' => [
            'free' => 100,
            'pro' => 1000,
            'enterprise' => 10000,
        ],
    ],
    'docs' => [
        'enabled' => env('API_DOCS_ENABLED', true),
        'require_auth' => env('API_DOCS_REQUIRE_AUTH', false),
    ],
];
```

## API Guides

The package includes comprehensive guides:

- **Authentication** - API key creation and usage
- **Quick Start** - Getting started in 5 minutes
- **Rate Limiting** - Understanding limits and tiers
- **Webhooks** - Setting up and verifying webhooks
- **Errors** - Error codes and handling

Access at: `/api/guides`

## Requirements

- PHP 8.2+
- Laravel 11+ or 12+

## Changelog

See [changelog/2026/jan/features.md](changelog/2026/jan/features.md) for recent changes.

## Security

See [changelog/2026/jan/security.md](changelog/2026/jan/security.md) for security updates.

## License

EUPL-1.2 - See [LICENSE](../../LICENSE) for details.
