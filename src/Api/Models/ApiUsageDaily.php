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
        $isSuccess = $usage->isSuccess() ? 1 : 0;
        $isError = $usage->status_code >= 400 ? 1 : 0;
        $responseTimeMs = (int) $usage->response_time_ms;
        $requestSize = (int) ($usage->request_size ?? 0);
        $responseSize = (int) ($usage->response_size ?? 0);

        $uniqueKey = [
            'api_key_id' => $usage->api_key_id,
            'workspace_id' => $usage->workspace_id,
            'date' => $usage->created_at->toDateString(),
            'endpoint' => $usage->endpoint,
            'method' => $usage->method,
        ];

        $values = [
            ...$uniqueKey,
            'request_count' => DB::raw("request_count + 1"),
            'success_count' => DB::raw("success_count + $isSuccess"),
            'error_count' => DB::raw("error_count + $isError"),
            'total_response_time_ms' => DB::raw("total_response_time_ms + $responseTimeMs"),
            'min_response_time_ms' => DB::raw("LEAST(IFNULL(min_response_time_ms, $responseTimeMs), $responseTimeMs)"),
            'max_response_time_ms' => DB::raw("GREATEST(IFNULL(max_response_time_ms, $responseTimeMs), $responseTimeMs)"),
            'total_request_size' => DB::raw("total_request_size + $requestSize"),
            'total_response_size' => DB::raw("total_response_size + $responseSize"),
            'updated_at' => now(),
        ];

        static::query()->upsert(
            [$values],
            ['api_key_id', 'workspace_id', 'date', 'endpoint', 'method'],
            [
                'request_count',
                'success_count',
                'error_count',
                'total_response_time_ms',
                'min_response_time_ms',
                'max_response_time_ms',
                'total_request_size',
                'total_response_size',
                'updated_at',
            ]
        );

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
