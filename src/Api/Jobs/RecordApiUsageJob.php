<?php

declare(strict_types=1);

namespace Mod\Api\Jobs;

use Core\Api\Models\ApiUsage;
use Core\Api\Models\ApiUsageDaily;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Record API usage aggregation in the background.
 *
 * Offloads the database-intensive daily aggregation from the API request cycle.
 */
class RecordApiUsageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Delete the job if its models no longer exist.
     */
    public bool $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ApiUsage $usage
    ) {
        // Use dedicated usage queue if configured
        $this->queue = config('api.usage.queue', 'default');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        ApiUsageDaily::recordFromUsage($this->usage);
    }

    /**
     * Get the tags for the job.
     *
     * @return array<string>
     */
    public function tags(): array
    {
        return [
            'usage',
            'api_key:'.$this->usage->api_key_id,
            'workspace:'.$this->usage->workspace_id,
        ];
    }
}
