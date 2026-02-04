<?php

declare(strict_types=1);

use Core\Api\Jobs\RecordApiUsageJob;
use Core\Api\Jobs\UpdateApiKeyLastUsedJob;
use Core\Api\Models\ApiKey;
use Core\Api\Services\ApiUsageService;
use Core\Tenant\Models\User;
use Core\Tenant\Models\Workspace;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Bus::fake();
    Cache::flush();

    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create();
});

it('dispatches UpdateApiKeyLastUsedJob when recording usage', function () {
    $result = ApiKey::generate(
        $this->workspace->id,
        $this->user->id,
        'Test Key'
    );
    $apiKey = $result['api_key'];

    $apiKey->recordUsage();

    Bus::assertDispatched(UpdateApiKeyLastUsedJob::class, function ($job) use ($apiKey) {
        return $job->apiKeyId === $apiKey->id;
    });
});

it('debounces UpdateApiKeyLastUsedJob using cache', function () {
    $result = ApiKey::generate(
        $this->workspace->id,
        $this->user->id,
        'Debounce Test Key'
    );
    $apiKey = $result['api_key'];

    // First call should dispatch
    $apiKey->recordUsage();
    Bus::assertDispatched(UpdateApiKeyLastUsedJob::class, 1);

    // Second call immediately after should not dispatch
    $apiKey->recordUsage();
    Bus::assertDispatched(UpdateApiKeyLastUsedJob::class, 1);

    // Clear cache and it should dispatch again
    Cache::flush();
    $apiKey->recordUsage();
    Bus::assertDispatched(UpdateApiKeyLastUsedJob::class, 2);
});

it('dispatches RecordApiUsageJob when recording detailed usage', function () {
    $result = ApiKey::generate(
        $this->workspace->id,
        $this->user->id,
        'Usage Test Key'
    );
    $apiKey = $result['api_key'];

    $service = new ApiUsageService();
    $service->record(
        apiKeyId: $apiKey->id,
        workspaceId: $apiKey->workspace_id,
        endpoint: '/api/test',
        method: 'GET',
        statusCode: 200,
        responseTimeMs: 150
    );

    Bus::assertDispatched(RecordApiUsageJob::class, function ($job) use ($apiKey) {
        return $job->data['api_key_id'] === $apiKey->id &&
               $job->data['endpoint'] === '/api/test' &&
               $job->data['status_code'] === 200;
    });
});
