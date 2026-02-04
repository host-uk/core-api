<?php

declare(strict_types=1);

namespace Core\Api\Jobs;

use Core\Api\Models\WebhookDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Delivers webhook payloads to registered endpoints.
 *
 * Implements exponential backoff retry logic:
 * - Attempt 1: Immediate
 * - Attempt 2: 1 minute delay
 * - Attempt 3: 5 minutes delay
 * - Attempt 4: 30 minutes delay
 * - Attempt 5: 2 hours delay
 * - Attempt 6 (final): 24 hours delay
 */
class DeliverWebhookJob implements ShouldQueue
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
     * The number of times the job may be attempted.
     * We handle retries manually with exponential backoff.
     */
    public int $tries = 1;

    /**
     * The attempt number this job is intended for.
     */
    public int $attemptNumber;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public WebhookDelivery $delivery
    ) {
        $this->attemptNumber = $delivery->attempt;

        // Use dedicated webhook queue if configured
        $this->queue = config('api.webhooks.queue', 'default');

        $connection = config('api.webhooks.queue_connection');
        if ($connection) {
            $this->connection = $connection;
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1. Idempotency check and mark as processing
        $shouldProceed = DB::transaction(function () {
            $delivery = WebhookDelivery::query()
                ->where('id', $this->delivery->id)
                ->lockForUpdate()
                ->first();

            if (! $delivery) {
                return false;
            }

            // If attempt number has changed, this is a stale job
            if ($delivery->attempt !== $this->attemptNumber) {
                Log::info('Webhook delivery skipped - attempt mismatch', [
                    'delivery_id' => $delivery->id,
                    'job_attempt' => $this->attemptNumber,
                    'current_attempt' => $delivery->attempt,
                ]);

                return false;
            }

            // If already successful, skip
            if ($delivery->status === WebhookDelivery::STATUS_SUCCESS) {
                return false;
            }

            // If already processing by another worker (and not stalled), skip
            if ($delivery->status === WebhookDelivery::STATUS_PROCESSING) {
                $isStalled = $delivery->processed_at && $delivery->processed_at->isBefore(now()->subMinutes(5));
                if (! $isStalled) {
                    return false;
                }
                Log::warning('Webhook delivery re-taking stalled processing job', [
                    'delivery_id' => $delivery->id,
                    'last_processed_at' => $delivery->processed_at,
                ]);
            }

            // Mark as processing
            $delivery->update([
                'status' => WebhookDelivery::STATUS_PROCESSING,
                'processed_at' => now(),
            ]);

            return true;
        });

        if (! $shouldProceed) {
            return;
        }

        // Refresh model to get latest data including endpoint relation
        $this->delivery->refresh();

        // Don't deliver if endpoint is disabled
        $endpoint = $this->delivery->endpoint;
        if (! $endpoint || ! $endpoint->shouldReceive($this->delivery->event_type)) {
            Log::info('Webhook delivery skipped - endpoint inactive or does not receive this event', [
                'delivery_id' => $this->delivery->id,
                'event_type' => $this->delivery->event_type,
            ]);

            return;
        }

        // Get delivery payload with signature headers
        $deliveryPayload = $this->delivery->getDeliveryPayload();
        $timeout = config('api.webhooks.timeout', 30);

        Log::info('Attempting webhook delivery', [
            'delivery_id' => $this->delivery->id,
            'endpoint_url' => $endpoint->url,
            'event_type' => $this->delivery->event_type,
            'attempt' => $this->delivery->attempt,
        ]);

        try {
            $response = Http::timeout($timeout)
                ->withHeaders($deliveryPayload['headers'])
                ->withBody($deliveryPayload['body'], 'application/json')
                ->post($endpoint->url);

            $statusCode = $response->status();
            $responseBody = $response->body();

            // Success is any 2xx status code
            if ($response->successful()) {
                DB::transaction(function () use ($statusCode, $responseBody) {
                    $delivery = WebhookDelivery::query()
                        ->where('id', $this->delivery->id)
                        ->lockForUpdate()
                        ->first();

                    if (! $delivery || $delivery->attempt !== $this->attemptNumber) {
                        return;
                    }

                    $delivery->markSuccess($statusCode, $responseBody);

                    Log::info('Webhook delivered successfully', [
                        'delivery_id' => $this->delivery->id,
                        'status_code' => $statusCode,
                    ]);
                });

                return;
            }

            // Non-2xx response - mark as failed and potentially retry
            $this->handleFailure($statusCode, $responseBody);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Connection timeout or refused
            $this->handleFailure(0, 'Connection failed: '.$e->getMessage());

        } catch (\Throwable $e) {
            // Unexpected error
            $this->handleFailure(0, 'Unexpected error: '.$e->getMessage());

            Log::error('Webhook delivery unexpected error', [
                'delivery_id' => $this->delivery->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Handle a failed delivery attempt.
     */
    protected function handleFailure(int $statusCode, ?string $responseBody): void
    {
        DB::transaction(function () use ($statusCode, $responseBody) {
            $delivery = WebhookDelivery::query()
                ->where('id', $this->delivery->id)
                ->lockForUpdate()
                ->first();

            if (! $delivery || $delivery->attempt !== $this->attemptNumber) {
                return;
            }

            Log::warning('Webhook delivery failed', [
                'delivery_id' => $this->delivery->id,
                'attempt' => $this->delivery->attempt,
                'status_code' => $statusCode,
                'can_retry' => $this->delivery->canRetry(),
            ]);

            // Mark as failed (this also schedules retry if attempts remain)
            $delivery->markFailed($statusCode, $responseBody);

            // If we can retry, dispatch a new job with the appropriate delay
            if ($this->delivery->canRetry() && $this->delivery->next_retry_at) {
                $delay = $this->delivery->next_retry_at->diffInSeconds(now());

                Log::info('Scheduling webhook retry', [
                    'delivery_id' => $this->delivery->id,
                    'next_attempt' => $this->delivery->attempt,
                    'delay_seconds' => $delay,
                    'next_retry_at' => $this->delivery->next_retry_at->toIso8601String(),
                ]);

                // Dispatch retry with calculated delay after the transaction commits
                self::dispatch($delivery->fresh())->delay($delay)->afterCommit();
            }
        });
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Webhook delivery job failed completely', [
            'delivery_id' => $this->delivery->id,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Get the tags for the job.
     *
     * @return array<string>
     */
    public function tags(): array
    {
        return [
            'webhook',
            'webhook:'.$this->delivery->webhook_endpoint_id,
            'event:'.$this->delivery->event_type,
        ];
    }
}
