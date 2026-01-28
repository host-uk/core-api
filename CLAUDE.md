# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Package Overview

This is `host-uk/core-api`, a Laravel package providing REST API infrastructure: OpenAPI documentation, rate limiting, webhook signing, and secure API key management. Part of the Core PHP Framework monorepo ecosystem.

## Commands

```bash
./vendor/bin/pest                    # Run all tests
./vendor/bin/pest --filter=ApiKey    # Run tests matching "ApiKey"
./vendor/bin/pint --dirty            # Format changed files
./vendor/bin/pint src/               # Format specific directory
```

## Package Structure

```
src/
├── Api/                        # Core\Api namespace
│   ├── Boot.php               # Service provider, event listeners
│   ├── config.php             # Package configuration
│   ├── Middleware/            # API auth, rate limiting, scopes
│   ├── Models/                # ApiKey, WebhookEndpoint, etc.
│   ├── Services/              # Business logic (webhooks, keys)
│   ├── Documentation/         # OpenAPI spec generation
│   │   ├── Attributes/        # #[ApiTag], #[ApiResponse], etc.
│   │   ├── Extensions/        # API docs extensions
│   │   └── OpenApiBuilder.php # Spec generator
│   ├── RateLimit/             # Per-endpoint rate limiting
│   ├── Resources/             # API JSON resources
│   └── Tests/Feature/         # Package tests
│
└── Website/Api/               # Core\Website\Api namespace
    ├── Boot.php               # Web routes service provider
    ├── Controllers/           # DocsController
    ├── Routes/web.php         # /api/docs, /api/guides routes
    ├── Services/              # OpenApiGenerator
    └── View/Blade/            # API docs UI templates
```

## Architecture

**Two-namespace design:**
- `Core\Api\` — Backend API logic (middleware, models, services)
- `Core\Website\Api\` — Frontend documentation UI (controllers, views)

**Event-driven boot** via `$listens` array in Boot classes:
```php
public static array $listens = [
    AdminPanelBooting::class => 'onAdminPanel',
    ApiRoutesRegistering::class => 'onApiRoutes',
    ConsoleBooting::class => 'onConsole',
];
```

**Key components:**
- `AuthenticateApiKey` — Validates API keys (bcrypt + legacy SHA-256)
- `EnforceApiScope` — Scope-based permissions (`read`, `write`, wildcards)
- `RateLimitApi` — Tier-based rate limiting with burst allowance
- `WebhookService` — HMAC-SHA256 signed webhook delivery

## OpenAPI Documentation Attributes

```php
use Core\Api\Documentation\Attributes\{ApiTag, ApiResponse, ApiParameter, ApiSecurity, ApiHidden};

#[ApiTag('Products')]
#[ApiResponse(200, ProductResource::class)]
#[ApiSecurity('apiKey')]
class ProductController
{
    #[ApiParameter('filter', 'query', 'string', 'Filter products')]
    public function index() { }
}
```

## Conventions

- UK English (colour, organisation, centre)
- PSR-12 with `declare(strict_types=1);`
- Type hints on all parameters and return types
- Pest for testing (not PHPUnit directly)
- Font Awesome Pro icons (not Heroicons)
- Flux Pro components (not vanilla Alpine)

## License

EUPL-1.2 (copyleft applies to Core\ namespace)