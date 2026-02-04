<?php

declare(strict_types=1);

use Mod\Api\Models\ApiKey;
use Mod\Api\Models\ApiUsage;
use Mod\Api\Models\ApiUsageDaily;
use Mod\Tenant\Models\User;
use Mod\Tenant\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('record from usage query count is optimized', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($user->id, [
        'role' => 'owner',
        'is_default' => true,
    ]);

    $result = ApiKey::generate($workspace->id, $user->id, 'Test Key');
    $apiKey = $result['api_key'];

    $usage = ApiUsage::create([
        'api_key_id' => $apiKey->id,
        'workspace_id' => $workspace->id,
        'endpoint' => '/api/v1/test',
        'method' => 'GET',
        'status_code' => 200,
        'response_time_ms' => 100,
        'created_at' => now(),
    ]);

    DB::flushQueryLog();
    DB::enableQueryLog();

    ApiUsageDaily::recordFromUsage($usage);

    $queries = DB::getQueryLog();

    // Optimized implementation should perform 3 queries:
    // 1. upsert (ensure record exists)
    // 2. update (combined counters and min/max)
    // 3. first() (retrieve the record)
    expect($queries)->toHaveCount(3);
});
