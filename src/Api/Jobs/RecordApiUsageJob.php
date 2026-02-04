<?php

declare(strict_types=1);

namespace Core\Api\Jobs;

use Core\Api\Models\ApiUsage;
use Core\Api\Models\ApiUsageDaily;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job to record detailed API usage analytics in the background.
 */
class RecordApiUsageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data
    ) {
        $this->queue = config('api.queues.usage', 'default');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Record individual usage
        $usage = ApiUsage::create([
            'api_key_id' => $this->data['api_key_id'],
            'workspace_id' => $this->data['workspace_id'],
            'endpoint' => $this->data['endpoint'],
            'method' => strtoupper($this->data['method']),
            'status_code' => $this->data['status_code'],
            'response_time_ms' => $this->data['response_time_ms'],
            'request_size' => $this->data['request_size'],
            'response_size' => $this->data['response_size'],
            'ip_address' => $this->data['ip_address'],
            'user_agent' => $this->data['user_agent'] ? substr($this->data['user_agent'], 0, 500) : null,
            'created_at' => $this->data['created_at'] ?? now(),
        ]);

        // Update daily aggregation
        ApiUsageDaily::recordFromUsage($usage);
    }
}
