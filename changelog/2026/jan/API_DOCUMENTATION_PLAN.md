# API Documentation Plan

**Goal:** Expose Host Hub APIs via api.host.uk.com with OpenAPI documentation, enabling SDK generation for multiple languages.

---

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│                         api.host.uk.com                             │
│                                                                     │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │
│  │   Swagger    │  │    ReDoc     │  │     SDK Downloads        │  │
│  │   UI /docs   │  │  /reference  │  │  /sdks/php, /sdks/js...  │  │
│  └──────────────┘  └──────────────┘  └──────────────────────────┘  │
│                              │                                      │
│                              ▼                                      │
│                    ┌──────────────────┐                            │
│                    │   openapi.json   │                            │
│                    │   openapi.yaml   │                            │
│                    └────────┬─────────┘                            │
│                              │                                      │
└──────────────────────────────┼──────────────────────────────────────┘
                               │
                               ▼
┌──────────────────────────────────────────────────────────────────────┐
│                         Host Hub Laravel                             │
│                                                                      │
│  ┌─────────────────────────────────────────────────────────────┐    │
│  │                      API Routes                              │    │
│  │  /api/v1/workspaces, /api/v1/entitlements, /api/v1/commerce │    │
│  └─────────────────────────────────────────────────────────────┘    │
│                              │                                       │
│              ┌───────────────┼───────────────┐                      │
│              ▼               ▼               ▼                      │
│      ┌────────────┐  ┌────────────┐  ┌────────────┐                │
│      │ Scramble   │  │ Controller │  │ Form       │                │
│      │ (auto-gen) │  │ Attributes │  │ Requests   │                │
│      └────────────┘  └────────────┘  └────────────┘                │
│                                                                      │
│  Note: MCP tools may call these same endpoints internally.          │
│  For agent-native interface, see mcp.host.uk.com                    │
└──────────────────────────────────────────────────────────────────────┘
```

---

## Package Selection: Scramble

**Why Scramble over L5-Swagger:**
- Zero annotations required - generates from code
- Understands Laravel conventions (Form Requests, Resources, Policies)
- Active development, Laravel-native
- Supports PHP 8 attributes for customization when needed

```bash
composer require dedoc/scramble
```

---

## API Structure

### Version Strategy

```
/api/v1/*  - Current stable
/api/v2/*  - Future (when breaking changes needed)
```

Version in URL, not headers. Simple, cacheable, debuggable.

### Authentication

```
┌─────────────────────────────────────────────────────────────┐
│  Authentication Methods                                      │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1. API Keys (Primary for SDK usage)                        │
│     Authorization: Bearer <api_key>                         │
│     - Scoped to workspace                                   │
│     - Rotatable, revocable                                  │
│     - Rate limited per key                                  │
│                                                             │
│  2. OAuth 2.0 (For third-party apps)                        │
│     - Authorization code flow                               │
│     - Scopes map to entitlements                            │
│     - Managed via Passport                                  │
│                                                             │
│  3. Session (Internal dashboard calls)                      │
│     - Sanctum SPA authentication                            │
│     - CSRF protected                                        │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Endpoint Categories

```yaml
/api/v1:
  /auth:
    - POST /token           # Exchange credentials for token
    - DELETE /token         # Revoke token
    - GET /me               # Current user info

  /workspaces:
    - GET /                 # List user's workspaces
    - GET /{id}             # Get workspace details
    - POST /                # Create workspace
    - PATCH /{id}           # Update workspace
    - DELETE /{id}          # Delete workspace

  /workspaces/{workspace}:
    /members:
      - GET /               # List members
      - POST /              # Invite member
      - DELETE /{user}      # Remove member

    /entitlements:
      - GET /               # Get entitlement summary
      - GET /check/{feature} # Check specific feature
      - GET /usage          # Usage breakdown

    /biolinks:
      - GET /               # List biolinks
      - POST /              # Create biolink
      - GET /{id}           # Get biolink
      - PATCH /{id}         # Update biolink
      - DELETE /{id}        # Delete biolink

    /links:
      - GET /               # List short links
      - POST /              # Create short link
      - GET /{id}           # Get link details
      - PATCH /{id}         # Update link
      - DELETE /{id}        # Delete link
      - GET /{id}/stats     # Link analytics

    /qr-codes:
      - GET /               # List QR codes
      - POST /              # Create QR code
      - GET /{id}           # Get QR code
      - GET /{id}/download  # Download QR image

  /commerce:
    /subscriptions:
      - GET /               # Current subscription
      - POST /change        # Change plan (preview)
      - POST /change/confirm # Execute plan change
      - POST /cancel        # Cancel subscription

    /invoices:
      - GET /               # List invoices
      - GET /{id}           # Get invoice
      - GET /{id}/pdf       # Download PDF

    /payment-methods:
      - GET /               # List payment methods
      - POST /              # Add payment method
      - DELETE /{id}        # Remove payment method
      - POST /{id}/default  # Set as default

  /support:
    /tickets:
      - GET /               # List tickets
      - POST /              # Create ticket
      - GET /{id}           # Get ticket
      - POST /{id}/reply    # Reply to ticket

  /webhooks:
    - GET /                 # List webhook endpoints
    - POST /                # Create webhook endpoint
    - PATCH /{id}           # Update webhook
    - DELETE /{id}          # Delete webhook
    - GET /{id}/deliveries  # View delivery history
```

---

## Implementation

### Phase 1: Core Setup

```php
// config/scramble.php
return [
    'info' => [
        'title' => 'Host Hub API',
        'description' => 'API for managing workspaces, biolinks, short links, and commerce.',
        'version' => '1.0.0',
        'contact' => [
            'name' => 'Host UK Support',
            'url' => 'https://support.host.uk.com',
            'email' => 'api@host.uk.com',
        ],
    ],

    'servers' => [
        ['url' => 'https://api.host.uk.com', 'description' => 'Production'],
        ['url' => 'https://api.staging.host.uk.com', 'description' => 'Staging'],
    ],

    'api_path' => 'api/v1',
    'api_domain' => null,

    // Generate docs at this path
    'export_path' => 'api/openapi.json',
];
```

### Phase 2: API Key System

```php
// database/migrations/create_api_keys_table.php
Schema::create('api_keys', function (Blueprint $table) {
    $table->id();
    $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->string('key', 64)->unique(); // hashed
    $table->string('prefix', 8);         // visible prefix for identification
    $table->json('scopes')->nullable();  // ['read', 'write', 'delete']
    $table->timestamp('last_used_at')->nullable();
    $table->timestamp('expires_at')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['workspace_id', 'deleted_at']);
});

// app/Models/ApiKey.php
class ApiKey extends Model
{
    use SoftDeletes;

    protected $casts = [
        'scopes' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public static function generate(Workspace $workspace, User $user, string $name): array
    {
        $plainKey = Str::random(48);
        $prefix = Str::random(8);

        $apiKey = static::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'name' => $name,
            'key' => hash('sha256', $plainKey),
            'prefix' => $prefix,
            'scopes' => ['read', 'write'],
        ]);

        // Return plain key only once - never stored
        return [
            'api_key' => $apiKey,
            'plain_key' => "{$prefix}_{$plainKey}", // hk_xxxxxxxx_xxxxx...
        ];
    }

    public static function findByPlainKey(string $plainKey): ?static
    {
        [$prefix, $key] = explode('_', $plainKey, 2);

        return static::where('prefix', $prefix)
            ->where('key', hash('sha256', $key))
            ->whereNull('deleted_at')
            ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->first();
    }

    public function recordUsage(): void
    {
        $this->update(['last_used_at' => now()]);
    }
}
```

### Phase 3: Authentication Guard

```php
// app/Http/Middleware/AuthenticateApiKey.php
class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'error' => 'unauthorized',
                'message' => 'API key required',
            ], 401);
        }

        // Check if it's an API key (prefixed with hk_)
        if (str_starts_with($token, 'hk_')) {
            $apiKey = ApiKey::findByPlainKey($token);

            if (!$apiKey) {
                return response()->json([
                    'error' => 'unauthorized',
                    'message' => 'Invalid API key',
                ], 401);
            }

            $apiKey->recordUsage();

            // Set workspace context
            $request->setUserResolver(fn() => $apiKey->user);
            $request->attributes->set('api_key', $apiKey);
            $request->attributes->set('workspace', $apiKey->workspace);

            return $next($request);
        }

        // Fall back to Sanctum for OAuth tokens
        return app(Authenticate::class)->handle($request, $next, 'sanctum');
    }
}
```

### Phase 4: API Controllers

```php
// app/Http/Controllers/Api/V1/WorkspaceController.php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorkspaceResource;
use App\Http\Requests\Api\CreateWorkspaceRequest;
use App\Http\Requests\Api\UpdateWorkspaceRequest;

/**
 * @tags Workspaces
 */
class WorkspaceController extends Controller
{
    /**
     * List workspaces
     *
     * Returns all workspaces the authenticated user has access to.
     *
     * @response WorkspaceResource[]
     */
    public function index(Request $request)
    {
        $workspaces = $request->user()
            ->workspaces()
            ->with('subscription.package')
            ->paginate();

        return WorkspaceResource::collection($workspaces);
    }

    /**
     * Get workspace
     *
     * @response WorkspaceResource
     */
    public function show(Workspace $workspace)
    {
        $this->authorize('view', $workspace);

        return new WorkspaceResource(
            $workspace->load('subscription.package', 'members')
        );
    }

    /**
     * Create workspace
     *
     * @response 201 WorkspaceResource
     */
    public function store(CreateWorkspaceRequest $request)
    {
        $workspace = Workspace::create([
            'name' => $request->name,
            'slug' => $request->slug ?? Str::slug($request->name),
            'owner_id' => $request->user()->id,
        ]);

        return new WorkspaceResource($workspace);
    }
}
```

### Phase 5: API Resources (Response Schemas)

```php
// app/Http/Resources/WorkspaceResource.php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $created_at
 */
class WorkspaceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // Relationships
            'subscription' => new SubscriptionResource($this->whenLoaded('subscription')),
            'members' => UserResource::collection($this->whenLoaded('members')),
            'member_count' => $this->whenCounted('members'),

            // Computed
            'links' => [
                'self' => route('api.v1.workspaces.show', $this->id),
                'biolinks' => route('api.v1.workspaces.biolinks.index', $this->id),
                'entitlements' => route('api.v1.workspaces.entitlements.index', $this->id),
            ],
        ];
    }
}
```

---

## SDK Generation

### Target Registries (Maximum Developer Signal)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                          SDK Distribution Matrix                            │
├──────────────┬────────────────────┬─────────────────────┬───────────────────┤
│ Language     │ Registry           │ Audience            │ Generator         │
├──────────────┼────────────────────┼─────────────────────┼───────────────────┤
│ PHP          │ Packagist          │ Web devs            │ php               │
│ TypeScript   │ npm                │ Frontend/Node       │ typescript-fetch  │
│ Python       │ PyPI               │ Scripts/Data/AI     │ python            │
│ Go           │ pkg.go.dev         │ DevOps/Cloud/CLI    │ go                │
│ Rust         │ crates.io          │ Systems/Performance │ rust              │
│ Ruby         │ RubyGems           │ Rails/Web           │ ruby              │
│ Java         │ Maven Central      │ Enterprise          │ java              │
│ C#/.NET      │ NuGet              │ Enterprise/Windows  │ csharp-netcore    │
│ Kotlin       │ Maven Central      │ Android/Modern Java │ kotlin            │
│ Swift        │ Swift PM           │ Apple ecosystem     │ swift5            │
│ Dart         │ pub.dev            │ Flutter/Mobile      │ dart              │
├──────────────┴────────────────────┴─────────────────────┴───────────────────┤
│                        Infrastructure / DevOps                              │
├──────────────┬────────────────────┬─────────────────────┬───────────────────┤
│ Ansible      │ Ansible Galaxy     │ SysAdmins           │ SwaggerToAnsible  │
│ Terraform    │ Terraform Registry │ IaC DevOps          │ Custom provider   │
│ Pulumi       │ Pulumi Registry    │ Modern IaC          │ Pulumi bridge     │
│ CLI (Go)     │ Homebrew/apt/yum   │ Terminal users      │ go + goreleaser   │
│ GitHub Action│ GH Marketplace     │ CI/CD users         │ Custom            │
└──────────────┴────────────────────┴─────────────────────┴───────────────────┘
```

### Priority Tiers

**Tier 1 - Core (Day 1):**
- PHP, TypeScript, Python (your primary audiences)

**Tier 2 - High Signal (Week 1):**
- Go (DevOps love, plus builds CLI binary)
- Rust (growing fast, crates.io visibility)
- Ruby (still strong web community)

**Tier 3 - Enterprise (Week 2):**
- Java, Kotlin (Maven Central = enterprise discovery)
- C#/.NET (NuGet = Microsoft ecosystem)

**Tier 4 - Mobile/Niche:**
- Swift (Apple developers)
- Dart (Flutter mobile)

**Tier 5 - Infrastructure:**
- Ansible (you have SwaggerToAnsible)
- Terraform Provider (huge IaC market)
- CLI binary via Homebrew
- GitHub Action

### OpenAPI Generator Configs

```yaml
# sdk-config/php.yaml
generatorName: php
outputDir: ./sdks/php
additionalProperties:
  packageName: HostHubSdk
  invokerPackage: HostUK\HostHub
  apiPackage: Api
  modelPackage: Model
  composerVendorName: host-uk
  composerProjectName: hosthub-sdk
  gitUserId: host-uk
  gitRepoId: hosthub-php-sdk

# sdk-config/typescript.yaml
generatorName: typescript-fetch
outputDir: ./sdks/typescript
additionalProperties:
  npmName: "@host-uk/hosthub-sdk"
  npmVersion: "1.0.0"
  supportsES6: true
  typescriptThreePlus: true

# sdk-config/python.yaml
generatorName: python
outputDir: ./sdks/python
additionalProperties:
  packageName: hosthub
  projectName: hosthub-sdk
  packageVersion: "1.0.0"

# sdk-config/go.yaml
generatorName: go
outputDir: ./sdks/go
additionalProperties:
  packageName: hosthub
  moduleName: github.com/host-uk/hosthub-go

# sdk-config/rust.yaml
generatorName: rust
outputDir: ./sdks/rust
additionalProperties:
  packageName: hosthub
  packageVersion: "1.0.0"

# sdk-config/ruby.yaml
generatorName: ruby
outputDir: ./sdks/ruby
additionalProperties:
  gemName: hosthub
  gemVersion: "1.0.0"
  gemAuthor: "Host UK"
  gemHomepage: "https://api.host.uk.com"

# sdk-config/java.yaml
generatorName: java
outputDir: ./sdks/java
additionalProperties:
  groupId: com.hostuk
  artifactId: hosthub-sdk
  artifactVersion: "1.0.0"
  invokerPackage: com.hostuk.hosthub
  apiPackage: com.hostuk.hosthub.api
  modelPackage: com.hostuk.hosthub.model

# sdk-config/csharp.yaml
generatorName: csharp-netcore
outputDir: ./sdks/csharp
additionalProperties:
  packageName: HostUK.HostHub
  packageVersion: "1.0.0"
  targetFramework: "net6.0"

# sdk-config/kotlin.yaml
generatorName: kotlin
outputDir: ./sdks/kotlin
additionalProperties:
  groupId: com.hostuk
  artifactId: hosthub-sdk
  packageName: com.hostuk.hosthub

# sdk-config/swift.yaml
generatorName: swift5
outputDir: ./sdks/swift
additionalProperties:
  projectName: HostHubSDK
  podVersion: "1.0.0"

# sdk-config/dart.yaml
generatorName: dart
outputDir: ./sdks/dart
additionalProperties:
  pubName: hosthub
  pubVersion: "1.0.0"
  pubAuthor: "Host UK"
```

### CLI Tool (Go-based)

```go
// cmd/hosthub/main.go
// Built from Go SDK, distributed via:
// - Homebrew (macOS/Linux)
// - apt repository (Debian/Ubuntu)
// - yum repository (RHEL/CentOS)
// - Scoop (Windows)
// - Direct binary downloads

// Example usage:
// $ hosthub workspaces list
// $ hosthub links create --url https://example.com
// $ hosthub biolinks get bl_123
```

```yaml
# .goreleaser.yml
builds:
  - main: ./cmd/hosthub
    binary: hosthub
    goos: [linux, darwin, windows]
    goarch: [amd64, arm64]

brews:
  - tap:
      owner: host-uk
      name: homebrew-tap
    name: hosthub
    homepage: https://api.host.uk.com

nfpms:
  - package_name: hosthub
    vendor: Host UK
    formats: [deb, rpm]
```

### Terraform Provider

```
terraform-provider-hosthub/
├── internal/
│   └── provider/
│       ├── provider.go
│       ├── workspace_resource.go
│       ├── biolink_resource.go
│       └── link_resource.go
└── examples/
    └── main.tf
```

```hcl
# Example Terraform usage
terraform {
  required_providers {
    hosthub = {
      source  = "host-uk/hosthub"
      version = "~> 1.0"
    }
  }
}

provider "hosthub" {
  api_key = var.hosthub_api_key
}

resource "hosthub_workspace" "main" {
  name = "My Workspace"
  slug = "my-workspace"
}

resource "hosthub_biolink" "landing" {
  workspace_id = hosthub_workspace.main.id
  name         = "Landing Page"
  url          = "landing"
}
```

### GitHub Action

```yaml
# action.yml
name: 'Host Hub Action'
description: 'Manage Host Hub resources in CI/CD'
branding:
  icon: 'link'
  color: 'blue'

inputs:
  api-key:
    description: 'Host Hub API key'
    required: true
  command:
    description: 'Command to run (e.g., "links create")'
    required: true

runs:
  using: 'node16'
  main: 'dist/index.js'
```

```yaml
# Usage in workflow
- uses: host-uk/hosthub-action@v1
  with:
    api-key: ${{ secrets.HOSTHUB_API_KEY }}
    command: links create --url ${{ github.event.deployment.url }}
```

### Package Registry Summary

| Registry | Package Name | Install Command |
|----------|--------------|-----------------|
| Packagist | `host-uk/hosthub-sdk` | `composer require host-uk/hosthub-sdk` |
| npm | `@host-uk/hosthub-sdk` | `npm install @host-uk/hosthub-sdk` |
| PyPI | `hosthub` | `pip install hosthub` |
| pkg.go.dev | `github.com/host-uk/hosthub-go` | `go get github.com/host-uk/hosthub-go` |
| crates.io | `hosthub` | `cargo add hosthub` |
| RubyGems | `hosthub` | `gem install hosthub` |
| Maven | `com.hostuk:hosthub-sdk` | Maven/Gradle dependency |
| NuGet | `HostUK.HostHub` | `dotnet add package HostUK.HostHub` |
| pub.dev | `hosthub` | `dart pub add hosthub` |
| Swift PM | `HostHubSDK` | Package.swift dependency |
| Ansible Galaxy | `hostuk.hosthub` | `ansible-galaxy collection install hostuk.hosthub` |
| Terraform | `host-uk/hosthub` | `required_providers` block |
| Homebrew | `hosthub` | `brew install host-uk/tap/hosthub` |
| GitHub Actions | `host-uk/hosthub-action` | `uses: host-uk/hosthub-action@v1` |

### Generation Script

```bash
#!/bin/bash
# scripts/generate-sdks.sh

OPENAPI_FILE="storage/app/openapi.json"
OUTPUT_DIR="./sdks"

# Export fresh OpenAPI spec
php artisan scramble:export

# Array of all SDK configs
SDKS=(
  "php"
  "typescript"
  "python"
  "go"
  "rust"
  "ruby"
  "java"
  "csharp"
  "kotlin"
  "swift"
  "dart"
)

# Generate all SDKs
for sdk in "${SDKS[@]}"; do
  echo "Generating $sdk SDK..."
  openapi-generator-cli generate \
    -i $OPENAPI_FILE \
    -c sdk-config/$sdk.yaml \
    -o $OUTPUT_DIR/$sdk
done

echo "All SDKs generated in $OUTPUT_DIR"
```

### Publish Script

```bash
#!/bin/bash
# scripts/publish-sdks.sh

VERSION=$1

if [ -z "$VERSION" ]; then
  echo "Usage: ./publish-sdks.sh <version>"
  exit 1
fi

# PHP → Packagist (auto via GitHub webhook)
echo "PHP: Push to host-uk/hosthub-php-sdk triggers Packagist"

# TypeScript → npm
cd sdks/typescript
npm version $VERSION
npm publish --access public
cd ../..

# Python → PyPI
cd sdks/python
python -m build
twine upload dist/*
cd ../..

# Go → pkg.go.dev (auto on push + tag)
cd sdks/go
git tag v$VERSION
git push origin v$VERSION
cd ../..

# Rust → crates.io
cd sdks/rust
cargo publish
cd ../..

# Ruby → RubyGems
cd sdks/ruby
gem build hosthub.gemspec
gem push hosthub-$VERSION.gem
cd ../..

# Java/Kotlin → Maven Central
cd sdks/java
./gradlew publish
cd ../..

# C# → NuGet
cd sdks/csharp
dotnet pack -c Release
dotnet nuget push **/*.nupkg --source https://api.nuget.org/v3/index.json
cd ../..

# Dart → pub.dev
cd sdks/dart
dart pub publish
cd ../..

echo "All SDKs published at version $VERSION"
```

### SDK Usage Examples

```php
// PHP SDK
use HostUK\HostHub\Api\WorkspacesApi;
use HostUK\HostHub\Configuration;

$config = Configuration::getDefaultConfiguration()
    ->setApiKey('Authorization', 'Bearer hk_xxxxxxxx_xxxxx...');

$api = new WorkspacesApi(config: $config);

$workspaces = $api->listWorkspaces();
foreach ($workspaces as $workspace) {
    echo $workspace->getName();
}
```

```typescript
// TypeScript SDK
import { WorkspacesApi, Configuration } from '@host-uk/hosthub-sdk';

const config = new Configuration({
  apiKey: 'Bearer hk_xxxxxxxx_xxxxx...',
});

const api = new WorkspacesApi(config);

const workspaces = await api.listWorkspaces();
workspaces.forEach(ws => console.log(ws.name));
```

```python
# Python SDK
from hosthub import Configuration, WorkspacesApi

config = Configuration()
config.api_key['Authorization'] = 'Bearer hk_xxxxxxxx_xxxxx...'

api = WorkspacesApi(configuration=config)

workspaces = api.list_workspaces()
for ws in workspaces:
    print(ws.name)
```

---

## Documentation Site (api.host.uk.com)

### Site Structure

```
api.host.uk.com/
├── /                    # Landing page (getting started)
├── /docs                # Swagger UI (interactive)
├── /reference           # ReDoc (beautiful reference)
├── /openapi.json        # Raw OpenAPI spec
├── /openapi.yaml        # YAML version
├── /sdks                # SDK download page
│   ├── /php             # PHP SDK + docs
│   ├── /typescript      # TypeScript SDK + docs
│   └── /python          # Python SDK + docs
├── /guides              # Integration guides
│   ├── /authentication  # Auth guide
│   ├── /webhooks        # Webhook setup
│   └── /rate-limits     # Rate limiting info
└── /changelog           # API changelog
```

### Landing Page Content

```markdown
# Host Hub API

Build integrations with Host Hub using our REST API.

## Quick Start

1. **Get an API Key**
   Navigate to Settings → API Keys in your workspace dashboard.

2. **Make Your First Request**
   ```bash
   curl https://api.host.uk.com/v1/workspaces \
     -H "Authorization: Bearer hk_your_api_key"
   ```

3. **Use an SDK**
   - [PHP SDK](/sdks/php)
   - [TypeScript SDK](/sdks/typescript)
   - [Python SDK](/sdks/python)

## Looking for Agent Integration?

For AI agent and MCP server integration, visit [mcp.host.uk.com](https://mcp.host.uk.com).
```

### Deployment

```yaml
# Separate static site or Laravel routes
# Option A: Static site (Astro/Next.js) pulling openapi.json
# Option B: Laravel routes serving documentation

# routes/api-docs.php (if using Laravel)
Route::domain('api.host.uk.com')->group(function () {
    Route::get('/', [ApiDocsController::class, 'landing']);
    Route::get('/docs', [ApiDocsController::class, 'swagger']);
    Route::get('/reference', [ApiDocsController::class, 'redoc']);
    Route::get('/openapi.json', [ApiDocsController::class, 'openApiJson']);
    Route::get('/openapi.yaml', [ApiDocsController::class, 'openApiYaml']);
    Route::get('/sdks', [ApiDocsController::class, 'sdks']);
    Route::get('/sdks/{language}', [ApiDocsController::class, 'sdkDownload']);
});
```

---

## Rate Limiting

```php
// config/api.php
return [
    'rate_limits' => [
        'default' => [
            'requests' => 1000,
            'per_minutes' => 60,
        ],
        'authenticated' => [
            'requests' => 5000,
            'per_minutes' => 60,
        ],
        'by_tier' => [
            'starter' => ['requests' => 1000, 'per_minutes' => 60],
            'pro' => ['requests' => 5000, 'per_minutes' => 60],
            'agency' => ['requests' => 20000, 'per_minutes' => 60],
        ],
    ],
];

// Rate limit headers included in all responses:
// X-RateLimit-Limit: 5000
// X-RateLimit-Remaining: 4987
// X-RateLimit-Reset: 1704067200
```

---

## Error Responses

```json
// Standard error format
{
  "error": "validation_error",
  "message": "The given data was invalid.",
  "details": {
    "name": ["The name field is required."],
    "slug": ["The slug has already been taken."]
  }
}

// Error codes
{
  "error": "unauthorized",           // 401 - Missing/invalid auth
  "error": "forbidden",              // 403 - No permission
  "error": "not_found",              // 404 - Resource not found
  "error": "validation_error",       // 422 - Invalid input
  "error": "rate_limit_exceeded",    // 429 - Too many requests
  "error": "internal_error",         // 500 - Server error
  "error": "entitlement_exceeded",   // 403 - Plan limit reached
}
```

---

## Webhooks

```php
// Webhook events
'workspace.created'
'workspace.updated'
'workspace.deleted'
'subscription.created'
'subscription.updated'
'subscription.cancelled'
'invoice.created'
'invoice.paid'
'invoice.failed'
'biolink.created'
'biolink.updated'
'link.created'
'link.clicked'  // High volume - opt-in only

// Webhook payload
{
  "id": "evt_123456",
  "type": "subscription.updated",
  "created_at": "2024-12-31T12:00:00Z",
  "data": {
    "subscription": { ... }
  },
  "workspace_id": 123
}

// Signature verification
$signature = hash_hmac('sha256', $payload, $webhookSecret);
// Compare with X-HostHub-Signature header
```

---

## MCP Intersection Note

The REST API and MCP tools share the same underlying services:

```
┌──────────────────┐     ┌──────────────────┐
│   REST API       │     │   MCP Tools      │
│ api.host.uk.com  │     │ mcp.host.uk.com  │
└────────┬─────────┘     └────────┬─────────┘
         │                        │
         └──────────┬─────────────┘
                    ▼
         ┌──────────────────┐
         │  Service Layer   │
         │  (Laravel)       │
         └──────────────────┘
```

PHP applications may call REST endpoints directly. AI agents should prefer the MCP interface at [mcp.host.uk.com](https://mcp.host.uk.com) for:
- Structured tool calling
- Entitlement-aware server discovery
- Agent-optimized response formats

---

## Implementation Phases

### Phase 1: Foundation
- [ ] Install Scramble
- [ ] Configure API versioning
- [ ] Create API key model and migration
- [ ] Implement API key authentication guard
- [ ] Set up rate limiting

### Phase 2: Core Endpoints
- [ ] Auth endpoints (token exchange)
- [ ] Workspace CRUD
- [ ] Entitlement checking
- [ ] Member management

### Phase 3: Product Endpoints
- [ ] Biolinks CRUD
- [ ] Short links CRUD
- [ ] QR codes CRUD
- [ ] Analytics endpoints

### Phase 4: Commerce Endpoints
- [ ] Subscription management
- [ ] Invoice listing
- [ ] Payment methods

### Phase 5: Documentation Site
- [ ] Deploy api.host.uk.com
- [ ] Swagger UI integration
- [ ] ReDoc integration
- [ ] SDK download pages

### Phase 6: SDK Generation
- [ ] Set up OpenAPI Generator
- [ ] Generate PHP SDK
- [ ] Generate TypeScript SDK
- [ ] Generate Python SDK
- [ ] Publish to package registries

### Phase 7: Webhooks
- [ ] Webhook endpoint management
- [ ] Event dispatching
- [ ] Delivery tracking
- [ ] Retry logic

---

## Files to Create/Modify

### New Files
```
app/
├── Http/
│   ├── Controllers/Api/V1/
│   │   ├── AuthController.php
│   │   ├── WorkspaceController.php
│   │   ├── EntitlementController.php
│   │   ├── BiolinkController.php
│   │   ├── LinkController.php
│   │   ├── QrCodeController.php
│   │   ├── SubscriptionController.php
│   │   ├── InvoiceController.php
│   │   └── WebhookController.php
│   ├── Middleware/
│   │   └── AuthenticateApiKey.php
│   ├── Resources/
│   │   ├── WorkspaceResource.php
│   │   ├── BiolinkResource.php
│   │   ├── LinkResource.php
│   │   └── ... (all API resources)
│   └── Requests/Api/
│       └── ... (all form requests)
├── Models/
│   ├── ApiKey.php
│   └── WebhookEndpoint.php
config/
├── scramble.php
└── api.php
routes/
├── api_v1.php
└── api-docs.php
database/migrations/
├── create_api_keys_table.php
└── create_webhook_endpoints_table.php
scripts/
└── generate-sdks.sh
sdk-config/
├── php.yaml
├── typescript.yaml
└── python.yaml
```

### Modified Files
- `routes/api.php` - Include versioned routes
- `app/Http/Kernel.php` - Register API middleware
- `composer.json` - Add Scramble dependency

---

*Created: 2024-12-31*
*Status: Planning*
*Destination: api.host.uk.com*
