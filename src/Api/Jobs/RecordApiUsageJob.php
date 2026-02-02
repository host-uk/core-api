<?php

declare(strict_types=1);

namespace Core\Api\Jobs;

use Core\Api\Models\ApiUsage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecordApiUsageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $apiKeyId,
        public int $workspaceId,
        public string $endpoint,
        public string $method,
        public int $statusCode,
        public int $responseTimeMs,
        public ?int $requestSize = null,
        public ?int $responseSize = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        ApiUsage::create([
            'api_key_id' => $this->apiKeyId,
            'workspace_id' => $this->workspaceId,
            'endpoint' => $this->endpoint,
            'method' => strtoupper($this->method),
            'status_code' => $this->statusCode,
            'response_time_ms' => $this->responseTimeMs,
            'request_size' => $this->requestSize,
            'response_size' => $this->responseSize,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent ? substr($this->userAgent, 0, 500) : null,
            'created_at' => now(),
        ]);
    }
}
