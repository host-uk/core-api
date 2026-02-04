<?php

declare(strict_types=1);

namespace Core\Api\Tests\Feature;

use Core\Api\Models\ApiKey;
use Core\Api\Models\ApiUsage;
use Core\Api\Models\ApiUsageDaily;
use Mod\Tenant\Models\User;
use Mod\Tenant\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApiUsagePerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $workspace;
    protected $apiKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->create();
        $this->workspace->users()->attach($this->user->id, [
            'role' => 'owner',
            'is_default' => true,
        ]);

        $result = ApiKey::generate($this->workspace->id, $this->user->id, 'Test Key');
        $this->apiKey = $result['api_key'];
    }

    public function test_record_from_usage_query_count()
    {
        $usage = ApiUsage::create([
            'api_key_id' => $this->apiKey->id,
            'workspace_id' => $this->workspace->id,
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
        $this->assertCount(3, $queries);
    }
}
