<?php

declare(strict_types=1);

namespace Core\Api\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Core\Tenant\Models\Workspace;

/**
 * API Usage Daily - aggregated daily API statistics.
 *
 * Pre-computed daily stats for efficient reporting and dashboards.
 */
class ApiUsageDaily extends Model
{
    protected $table = 'api_usage_daily';

    protected $fillable = [
        'api_key_id',
        'workspace_id',
        'date',
        'endpoint',
        'method',
        'request_count',
        'success_count',
        'error_count',
        'total_response_time_ms',
        'min_response_time_ms',
        'max_response_time_ms',
        'total_request_size',
        'total_response_size',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Update or create daily stats from a usage record.
     *
     * Uses Laravel's upsert() for database portability while maintaining
     * atomic operations. For increment operations, we use a two-step approach:
     * first upsert the base record, then atomically update counters.
     */
    public static function recordFromUsage(ApiUsage $usage): static
    {
        $isSuccess = $usage->isSuccess();
        $isError = $usage->status_code >= 400;
        $date = $usage->created_at->toDateString();
        $responseTimeMs = (int) $usage->response_time_ms;
        $now = now();

        // Unique key for this daily aggregation
        $uniqueKey = [
            'api_key_id' => $usage->api_key_id,
            'workspace_id' => $usage->workspace_id,
            'date' => $date,
            'endpoint' => $usage->endpoint,
            'method' => $usage->method,
        ];

        // Ensure the record exists with upsert (database-portable).
        // If it exists, we only touch updated_at to satisfy the upsert.
        static::upsert(
            [
                ...$uniqueKey,
                'request_count' => 0,
                'success_count' => 0,
                'error_count' => 0,
                'total_response_time_ms' => 0,
                'total_request_size' => 0,
                'total_response_size' => 0,
                'min_response_time_ms' => null,
                'max_response_time_ms' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['api_key_id', 'workspace_id', 'date', 'endpoint', 'method'],
            ['updated_at']
        );

        // Combined update for all metrics in a single query.
        // Uses CASE statements for conditional min/max updates to maintain portability.
        static::where($uniqueKey)->update([
            'request_count' => DB::raw('request_count + 1'),
            'success_count' => DB::raw('success_count + '.($isSuccess ? 1 : 0)),
            'error_count' => DB::raw('error_count + '.($isError ? 1 : 0)),
            'total_response_time_ms' => DB::raw('total_response_time_ms + '.$responseTimeMs),
            'total_request_size' => DB::raw('total_request_size + '.(int) ($usage->request_size ?? 0)),
            'total_response_size' => DB::raw('total_response_size + '.(int) ($usage->response_size ?? 0)),
            'min_response_time_ms' => DB::raw("CASE
                WHEN min_response_time_ms IS NULL OR min_response_time_ms > {$responseTimeMs}
                THEN {$responseTimeMs}
                ELSE min_response_time_ms
            END"),
            'max_response_time_ms' => DB::raw("CASE
                WHEN max_response_time_ms IS NULL OR max_response_time_ms < {$responseTimeMs}
                THEN {$responseTimeMs}
                ELSE max_response_time_ms
            END"),
            'updated_at' => $now,
        ]);

        // Retrieve the final record for return
        return static::where($uniqueKey)->first();
    }

    /**
     * Calculate average response time.
     */
    public function getAverageResponseTimeMsAttribute(): float
    {
        if ($this->request_count === 0) {
            return 0;
        }

        return round($this->total_response_time_ms / $this->request_count, 2);
    }

    /**
     * Calculate success rate percentage.
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->request_count === 0) {
            return 100;
        }

        return round(($this->success_count / $this->request_count) * 100, 2);
    }

    // Relationships
    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    // Scopes
    public function scopeForKey($query, int $apiKeyId)
    {
        return $query->where('api_key_id', $apiKeyId);
    }

    public function scopeForWorkspace($query, int $workspaceId)
    {
        return $query->where('workspace_id', $workspaceId);
    }

    public function scopeForEndpoint($query, string $endpoint)
    {
        return $query->where('endpoint', $endpoint);
    }

    public function scopeBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }
}
