<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Api\Controllers\McpApiController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Yaml\Exception\ParseException;
use Tests\TestCase;

uses(TestCase::class);

it('returns empty servers when registry YAML is invalid', function () {
    // Mock Cache to always run the closure
    Cache::shouldReceive('remember')
        ->once()
        ->with('mcp:registry', 600, \Closure::class)
        ->andReturnUsing(fn($key, $ttl, $callback) => $callback());

    Log::shouldReceive('error')->atLeast()->once();

    $controller = new class extends McpApiController {
        public function callLoadRegistry(): array
        {
            return $this->loadRegistry();
        }

        protected function parseYamlFile(string $path): mixed
        {
            return ['servers' => 'not-an-array']; // Invalid according to schema
        }
    };

    $result = $controller->callLoadRegistry();

    expect($result)->toBe(['servers' => []]);
});

it('returns null when server YAML is missing required fields', function () {
    Cache::shouldReceive('remember')
        ->once()
        ->with('mcp:server:invalid', 600, \Closure::class)
        ->andReturnUsing(fn($key, $ttl, $callback) => $callback());

    Log::shouldReceive('error')->atLeast()->once();

    $controller = new class extends McpApiController {
        public function callLoadServerFull(string $id): ?array
        {
            return $this->loadServerFull($id);
        }

        protected function parseYamlFile(string $path): mixed
        {
            return ['id' => 'only-id']; // Missing 'name'
        }
    };

    $result = $controller->callLoadServerFull('invalid');

    expect($result)->toBeNull();
});

it('returns null when server YAML parsing throws exception', function () {
    Cache::shouldReceive('remember')
        ->once()
        ->with('mcp:server:malformed', 600, \Closure::class)
        ->andReturnUsing(fn($key, $ttl, $callback) => $callback());

    Log::shouldReceive('error')->atLeast()->once();

    $controller = new class extends McpApiController {
        public function callLoadServerFull(string $id): ?array
        {
            return $this->loadServerFull($id);
        }

        protected function parseYamlFile(string $path): mixed
        {
            throw new ParseException('Malformed YAML');
        }
    };

    $result = $controller->callLoadServerFull('malformed');

    expect($result)->toBeNull();
});
