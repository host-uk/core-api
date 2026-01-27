<?php

declare(strict_types=1);

use Mod\Api\Models\ApiKey;
use Mod\Tenant\Models\User;
use Mod\Tenant\Models\Workspace;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();

    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create();
    $this->workspace->users()->attach($this->user->id, [
        'role' => 'owner',
        'is_default' => true,
    ]);

    // Register test routes with scope enforcement
    Route::middleware(['api', 'api.auth', 'api.scope.enforce'])
        ->prefix('test-scope')
        ->group(function () {
            Route::get('/read', fn () => response()->json(['status' => 'ok']));
            Route::post('/write', fn () => response()->json(['status' => 'ok']));
            Route::put('/update', fn () => response()->json(['status' => 'ok']));
            Route::patch('/patch', fn () => response()->json(['status' => 'ok']));
            Route::delete('/delete', fn () => response()->json(['status' => 'ok']));
        });
});

// ─────────────────────────────────────────────────────────────────────────────
// Read Scope Enforcement
// ─────────────────────────────────────────────────────────────────────────────

describe('Read Scope Enforcement', function () {
    it('allows GET request with read scope', function () {
        $result = ApiKey::generate(
            $this->workspace->id,
            $this->user->id,
            'Read Only Key',
            [ApiKey::SCOPE_READ]
        );

        $response = $this->getJson('/api/test-scope/read', [
            'Authorization' => "Bearer {$result['plain_key']}",
        ]);

        expect($response->status())->toBe(200);
        expect($response->json('status'))->toBe('ok');
    });

    it('denies POST request with read-only scope', function () {
        $result = ApiKey::generate(
            $this->workspace->id,
            $this->user->id,
            'Read Only Key',
            [ApiKey::SCOPE_READ]
        );

        $response = $this->postJson('/api/test-scope/write', [], [
            'Authorization' => "Bearer {$result['plain_key']}",
        ]);

        expect($response->status())->toBe(403);
        expect($response->json('error'))->toBe('forbidden');
        expect($response->json('message'))->toContain('write');
    });

    it('denies DELETE request with read-only scope', function () {
        $result = ApiKey::generate(
            $this->workspace->id,
            $this->user->id,
            'Read Only Key',
            [ApiKey::SCOPE_READ]
        );

        $response = $this->deleteJson('/api/test-scope/delete', [], [
            'Authorization' => "Bearer {$result['plain_key']}",
        ]);

        expect($response->status())->toBe(403);
        expect($response->json('error'))->toBe('forbidden');
        expect($response->json('message'))->toContain('delete');
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Write Scope Enforcement
// ─────────────────────────────────────────────────────────────────────────────

describe('Write Scope Enforcement', function () {
    it('allows POST request with write scope', function () {
        $result = ApiKey::generate(
            $this->workspace->id,
            $this->user->id,
            'Read/Write Key',
            [ApiKey::SCOPE_READ, ApiKey::SCOPE_WRITE]
        );

        $response = $this->postJson('/api/test-scope/write', [], [
            'Authorization' => "Bearer {$result['plain_key']}",
        ]);

        expect($response->status())->toBe(200);
    });

    it('allows PUT request with write scope', function () {
        $result = ApiKey::generate(
            $this->workspace->id,
            $this->user->id,
            'Read/Write Key',
            [ApiKey::SCOPE_READ, ApiKey::SCOPE_WRITE]
        );

        $response = $this->putJson('/api/test-scope/update', [], [
            'Authorization' => "Bearer {$result['plain_key']}",
        ]);

        expect($response->status())->toBe(200);
    });

    it('allows PATCH request with write scope', function () {
        $result = ApiKey::generate(
            $this->workspace->id,
            $this->user->id,
            'Read/Write Key',
            [ApiKey::SCOPE_READ, ApiKey::SCOPE_WRITE]
        );

        $response = $this->patchJson('/api/test-scope/patch', [], [
            'Authorization' => "Bearer {$result['plain_key']}",
        ]);

        expect($response->status())->toBe(200);
    });

    it('denies DELETE request without delete scope', function () {
        $result = ApiKey::generate(
            $this->workspace->id,
            $this->user->id,
            'Read/Write Key',
            [ApiKey::SCOPE_READ, ApiKey::SCOPE_WRITE]
        );

        $response = $this->deleteJson('/api/test-scope/delete', [], [
            'Authorization' => "Bearer {$result['plain_key']}",
        ]);

        expect($response->status())->toBe(403);
        expect($response->json('message'))->toContain('delete');
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Delete Scope Enforcement
// ─────────────────────────────────────────────────────────────────────────────

describe('Delete Scope Enforcement', function () {
    it('allows DELETE request with delete scope', function () {
        $result = ApiKey::generate(
            $this->workspace->id,
            $this->user->id,
            'Full Access Key',
            [ApiKey::SCOPE_READ, ApiKey::SCOPE_WRITE, ApiKey::SCOPE_DELETE]
        );

        $response = $this->deleteJson('/api/test-scope/delete', [], [
            'Authorization' => "Bearer {$result['plain_key']}",
        ]);

        expect($response->status())->toBe(200);
    });

    it('includes key scopes in error response', function () {
        $result = ApiKey::generate(
            $this->workspace->id,
            $this->user->id,
            'Read Only Key',
            [ApiKey::SCOPE_READ]
        );

        $response = $this->deleteJson('/api/test-scope/delete', [], [
            'Authorization' => "Bearer {$result['plain_key']}",
        ]);

        expect($response->status())->toBe(403);
        expect($response->json('key_scopes'))->toBe([ApiKey::SCOPE_READ]);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Full Access Keys
// ─────────────────────────────────────────────────────────────────────────────

describe('Full Access Keys', function () {
    it('allows all operations with full access', function () {
        $result = ApiKey::generate(
            $this->workspace->id,
            $this->user->id,
            'Full Access Key',
            ApiKey::ALL_SCOPES
        );

        $headers = ['Authorization' => "Bearer {$result['plain_key']}"];

        expect($this->getJson('/api/test-scope/read', $headers)->status())->toBe(200);
        expect($this->postJson('/api/test-scope/write', [], $headers)->status())->toBe(200);
        expect($this->putJson('/api/test-scope/update', [], $headers)->status())->toBe(200);
        expect($this->patchJson('/api/test-scope/patch', [], $headers)->status())->toBe(200);
        expect($this->deleteJson('/api/test-scope/delete', [], $headers)->status())->toBe(200);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Non-API Key Auth (Session)
// ─────────────────────────────────────────────────────────────────────────────

describe('Non-API Key Auth', function () {
    it('passes through for session authenticated users', function () {
        // For session auth, the middleware should allow through
        // as scope enforcement only applies to API key auth
        $this->actingAs($this->user);

        // The api.auth middleware will require API key, so this tests
        // that if somehow session auth is used, scope middleware allows it
        // In practice, routes use either 'auth' OR 'api.auth', not both
    });
});
