<?php

declare(strict_types=1);

use Core\Api\Models\ApiKey;
use Core\Mod\Mcp\Services\ToolVersionService;
use Illuminate\Support\Facades\Cache;
use Mod\Tenant\Models\User;
use Mod\Tenant\Models\Workspace;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();

    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create();
    $this->workspace->users()->attach($this->user->id, [
        'role' => 'owner',
        'is_default' => true,
    ]);

    // Create a default API key for testing
    $result = ApiKey::generate(
        $this->workspace->id,
        $this->user->id,
        'Test Key',
        [ApiKey::SCOPE_READ, ApiKey::SCOPE_WRITE]
    );
    $this->apiKey = $result['api_key'];
    $this->plainKey = $result['plain_key'];
    $this->headers = ['Authorization' => "Bearer {$this->plainKey}"];

    // Mock server definition in cache
    $this->mockServerId = 'hosthub-agent';
    $this->mockServerData = [
        'id' => $this->mockServerId,
        'name' => 'HostHub Agent',
        'tools' => [
            [
                'name' => 'test-tool',
                'description' => 'A test tool',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'arg1' => ['type' => 'string'],
                    ],
                    'required' => ['arg1'],
                ],
            ],
        ],
    ];
    Cache::put("mcp:server:{$this->mockServerId}", $this->mockServerData, 600);
});

// ─────────────────────────────────────────────────────────────────────────────
// Tool Execution
// ─────────────────────────────────────────────────────────────────────────────

describe('Tool Execution', function () {
    it('returns 404 for unknown server', function () {
        $response = $this->postJson('/api/v1/mcp/tools/call', [
            'server' => 'nonexistent',
            'tool' => 'test-tool',
        ], $this->headers);

        $response->assertStatus(404);
        $response->assertJson(['error' => 'Server not found']);
    });

    it('returns 404 for unknown tool', function () {
        $response = $this->postJson('/api/v1/mcp/tools/call', [
            'server' => $this->mockServerId,
            'tool' => 'nonexistent-tool',
        ], $this->headers);

        $response->assertStatus(404);
        $response->assertJson(['error' => 'Tool not found']);
    });

    it('returns 422 for schema validation failures', function () {
        $response = $this->postJson('/api/mcp/tools/call', [
            'server' => $this->mockServerId,
            'tool' => 'test-tool',
            'arguments' => [], // Missing required arg1
        ], $this->headers);

        $response->assertStatus(422);
        $response->assertJsonPath('error_code', 'VALIDATION_ERROR');
        $response->assertJsonFragment(['Missing required argument: arg1']);
    });

    it('returns 422 for type validation failures', function () {
        $response = $this->postJson('/api/v1/mcp/tools/call', [
            'server' => $this->mockServerId,
            'tool' => 'test-tool',
            'arguments' => ['arg1' => 123], // Should be string
        ], $this->headers);

        $response->assertStatus(422);
        $response->assertJsonFragment(["Argument 'arg1' must be of type string"]);
    });

    it('successfully executes a tool', function () {
        $this->partialMock(\Core\Api\Controllers\McpApiController::class, function ($mock) {
            $mock->shouldAllowMockingProtectedMethods();
            $mock->shouldReceive('executeToolViaArtisan')
                ->once()
                ->with($this->mockServerId, 'test-tool', ['arg1' => 'val'])
                ->andReturn(['result' => 'success']);
        });

        $response = $this->postJson('/api/v1/mcp/tools/call', [
            'server' => $this->mockServerId,
            'tool' => 'test-tool',
            'arguments' => ['arg1' => 'val'],
        ], $this->headers);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'result' => ['result' => 'success'],
        ]);
    });

    it('handles process execution failures', function () {
        $this->partialMock(\Core\Api\Controllers\McpApiController::class, function ($mock) {
            $mock->shouldAllowMockingProtectedMethods();
            $mock->shouldReceive('executeToolViaArtisan')
                ->andThrow(new \RuntimeException('Process failed'));
        });

        $response = $this->postJson('/api/v1/mcp/tools/call', [
            'server' => $this->mockServerId,
            'tool' => 'test-tool',
            'arguments' => ['arg1' => 'val'],
        ], $this->headers);

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'error' => 'Process failed',
        ]);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Version Management
// ─────────────────────────────────────────────────────────────────────────────

describe('Version Management', function () {
    it('returns 410 for sunset versions', function () {
        $versionService = Mockery::mock(ToolVersionService::class);
        $versionService->shouldReceive('resolveVersion')->andReturn([
            'version' => null,
            'warning' => null,
            'error' => [
                'code' => 'TOOL_VERSION_SUNSET',
                'message' => 'This version has been sunset',
            ],
        ]);
        $this->app->instance(ToolVersionService::class, $versionService);

        $response = $this->postJson('/api/v1/mcp/tools/call', [
            'server' => $this->mockServerId,
            'tool' => 'test-tool',
            'version' => '1.0.0',
        ], $this->headers);

        $response->assertStatus(410);
        $response->assertJsonPath('error_code', 'TOOL_VERSION_SUNSET');
    });

    it('includes deprecation headers for deprecated versions', function () {
        // Create a mock version object
        $mockVersion = new class {
            public $version = '1.1.0';
            public $input_schema = null;
        };

        $versionService = Mockery::mock(ToolVersionService::class);
        $versionService->shouldReceive('resolveVersion')->andReturn([
            'version' => $mockVersion,
            'warning' => [
                'message' => 'This version is deprecated',
                'sunset_at' => '2026-12-31',
                'latest_version' => '2.0.0',
            ],
            'error' => null,
        ]);
        $this->app->instance(ToolVersionService::class, $versionService);

        $this->partialMock(\Core\Api\Controllers\McpApiController::class, function ($mock) {
            $mock->shouldAllowMockingProtectedMethods();
            $mock->shouldReceive('executeToolViaArtisan')->andReturn(['status' => 'ok']);
        });

        $response = $this->postJson('/api/v1/mcp/tools/call', [
            'server' => $this->mockServerId,
            'tool' => 'test-tool',
            'arguments' => ['arg1' => 'val'],
        ], $this->headers);

        $response->assertHeader('X-MCP-Deprecation-Warning', 'This version is deprecated');
        $response->assertHeader('X-MCP-Sunset-At', '2026-12-31');
        $response->assertHeader('X-MCP-Latest-Version', '2.0.0');
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Security Boundaries
// ─────────────────────────────────────────────────────────────────────────────

describe('Security Boundaries', function () {
    it('enforces server scope restrictions', function () {
        $this->apiKey->update(['server_scopes' => ['other-server']]);

        $response = $this->postJson('/api/v1/mcp/tools/call', [
            'server' => $this->mockServerId,
            'tool' => 'test-tool',
            'arguments' => ['arg1' => 'val'],
        ], $this->headers);

        $response->assertStatus(403);
    });

    it('enforces API key scope requirements', function () {
        $result = ApiKey::generate(
            $this->workspace->id,
            $this->user->id,
            'Read Only Key',
            [ApiKey::SCOPE_READ]
        );
        $headers = ['Authorization' => "Bearer {$result['plain_key']}"];

        $response = $this->postJson('/api/v1/mcp/tools/call', [
            'server' => $this->mockServerId,
            'tool' => 'test-tool',
            'arguments' => ['arg1' => 'val'],
        ], $headers);

        $response->assertStatus(403);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Rate Limiting
// ─────────────────────────────────────────────────────────────────────────────

describe('Rate Limiting', function () {
    it('includes rate limit headers', function () {
        $response = $this->getJson('/api/v1/mcp/servers', $this->headers);

        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining');
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Discovery Endpoints
// ─────────────────────────────────────────────────────────────────────────────

describe('Discovery Endpoints', function () {
    it('lists available servers', function () {
        Cache::put('mcp:registry', ['servers' => [['id' => $this->mockServerId]]], 600);

        $response = $this->getJson('/api/v1/mcp/servers', $this->headers);

        $response->assertOk();
        $response->assertJsonPath('count', 1);
        $response->assertJsonFragment(['id' => $this->mockServerId]);
    });

    it('gets server details', function () {
        $response = $this->getJson("/api/v1/mcp/servers/{$this->mockServerId}", $this->headers);

        $response->assertOk();
        $response->assertJsonPath('id', $this->mockServerId);
    });

    it('lists tools for a server', function () {
        $response = $this->getJson("/api/v1/mcp/servers/{$this->mockServerId}/tools", $this->headers);

        $response->assertOk();
        $response->assertJsonPath('count', 1);
        $response->assertJsonFragment(['name' => 'test-tool']);
    });
});
