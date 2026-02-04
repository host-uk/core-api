<?php

declare(strict_types=1);

namespace Core\Api\Jobs;

use Core\Api\Models\ApiKey;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job to update the API key's last_used_at timestamp in the background.
 */
class UpdateApiKeyLastUsedJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $apiKeyId
    ) {
        // Use a low-priority queue if available
        $this->queue = config('api.queues.usage', 'default');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $apiKey = ApiKey::find($this->apiKeyId);

        if ($apiKey) {
            $apiKey->update(['last_used_at' => now()]);
        }
    }
}
