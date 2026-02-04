<?php

declare(strict_types=1);

namespace Core\Api\Tests\Feature;

use Core\Api\Services\WebhookSecretRotationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Define a test model for the service.
 */
class TestWebhookRotationModel extends Model
{
    protected $table = 'test_webhook_rotation';
    protected $guarded = [];
    protected $casts = [
        'secret_rotated_at' => 'datetime',
    ];
}

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(WebhookSecretRotationService::class);

    // Create table for the test model if it doesn't exist
    if (! Schema::hasTable('test_webhook_rotation')) {
        Schema::create('test_webhook_rotation', function (Blueprint $table) {
            $table->id();
            $table->string('secret')->nullable();
            $table->string('previous_secret')->nullable();
            $table->timestamp('secret_rotated_at')->nullable();
            $table->unsignedInteger('grace_period_seconds')->nullable();
            $table->timestamps();
        });
    }
});

describe('Webhook Secret Rotation Service', function () {
    it('handles multiple rotations correctly', function () {
        $now = Carbon::parse('2025-01-01 12:00:00');
        Carbon::setTestNow($now);

        $webhook = TestWebhookRotationModel::create([
            'secret' => 'original_secret',
        ]);

        // First rotation
        $newSecret1 = $this->service->rotateSecret($webhook);
        $webhook->refresh();

        expect($webhook->secret)->toBe($newSecret1);
        expect($webhook->previous_secret)->toBe('original_secret');
        expect($webhook->secret_rotated_at->timestamp)->toBe($now->timestamp);

        // Second rotation (sequential)
        $now->addHour();
        Carbon::setTestNow($now);

        $newSecret2 = $this->service->rotateSecret($webhook);
        $webhook->refresh();

        expect($webhook->secret)->toBe($newSecret2);
        expect($webhook->previous_secret)->toBe($newSecret1);
        expect($webhook->secret_rotated_at->timestamp)->toBe($now->timestamp);

        Carbon::setTestNow(); // Reset
    });

    it('handles grace period expiry correctly', function () {
        $now = Carbon::parse('2025-01-01 12:00:00');
        Carbon::setTestNow($now);

        $rotatedAt = $now->copy();
        $gracePeriod = 3600; // 1 hour

        $webhook = TestWebhookRotationModel::create([
            'secret' => 'new_secret',
            'previous_secret' => 'old_secret',
            'secret_rotated_at' => $rotatedAt,
            'grace_period_seconds' => $gracePeriod,
        ]);

        $payload = '{"test": "data"}';
        $oldSignature = hash_hmac('sha256', $payload, 'old_secret');
        $newSignature = hash_hmac('sha256', $payload, 'new_secret');

        // Just before expiry (1 second before)
        Carbon::setTestNow($rotatedAt->copy()->addSeconds($gracePeriod - 1));

        $result = $this->service->verifySignature($webhook, $payload, $oldSignature);
        expect($result['valid'])->toBeTrue();
        expect($result['used_previous'])->toBeTrue();

        $result = $this->service->verifySignature($webhook, $payload, $newSignature);
        expect($result['valid'])->toBeTrue();
        expect($result['used_previous'])->toBeFalse();

        // At expiry (exact)
        Carbon::setTestNow($rotatedAt->copy()->addSeconds($gracePeriod));

        $result = $this->service->verifySignature($webhook, $payload, $oldSignature);
        expect($result['valid'])->toBeFalse(); // isBefore(graceEndsAt) will be false if now == graceEndsAt

        $result = $this->service->verifySignature($webhook, $payload, $newSignature);
        expect($result['valid'])->toBeTrue();

        Carbon::setTestNow(); // Reset
    });

    it('cleans up expired grace periods with large datasets', function () {
        $expiredCount = 150;
        $activeCount = 50;

        $now = Carbon::parse('2025-01-01 12:00:00');
        Carbon::setTestNow($now);

        // Create expired webhooks
        for ($i = 0; $i < $expiredCount; $i++) {
            TestWebhookRotationModel::create([
                'secret' => 'secret_'.$i,
                'previous_secret' => 'old_'.$i,
                'secret_rotated_at' => $now->copy()->subDays(2),
                'grace_period_seconds' => 86400, // 1 day
            ]);
        }

        // Create active webhooks
        for ($i = 0; $i < $activeCount; $i++) {
            TestWebhookRotationModel::create([
                'secret' => 'active_secret_'.$i,
                'previous_secret' => 'active_old_'.$i,
                'secret_rotated_at' => $now->copy()->subHours(1),
                'grace_period_seconds' => 86400,
            ]);
        }

        $cleanedCount = $this->service->cleanupExpiredGracePeriods(TestWebhookRotationModel::class);

        expect($cleanedCount)->toBe($expiredCount);
        expect(TestWebhookRotationModel::whereNotNull('previous_secret')->count())->toBe($activeCount);

        Carbon::setTestNow();
    });

    it('handles different time zones for grace period calculations', function () {
        $now = Carbon::parse('2025-01-01 12:00:00', 'UTC');
        Carbon::setTestNow($now);

        // Set a different application timezone
        $originalTimezone = config('app.timezone');
        config(['app.timezone' => 'America/New_York']);

        $rotatedAt = $now->copy()->subHours(2); // 10:00:00 UTC
        $webhook = TestWebhookRotationModel::create([
            'secret' => 'secret',
            'previous_secret' => 'old_secret',
            'secret_rotated_at' => $rotatedAt,
            'grace_period_seconds' => 3600, // 1 hour
        ]);

        // 2 hours passed since rotation, grace period is 1 hour
        expect($this->service->isInGracePeriod($webhook))->toBeFalse();

        $webhook->update(['grace_period_seconds' => 10800]); // 3 hours
        $webhook->refresh();

        expect($this->service->isInGracePeriod($webhook))->toBeTrue();

        config(['app.timezone' => $originalTimezone]);
        Carbon::setTestNow();
    });

    it('verifies signatures with different hash algorithms', function () {
        $webhook = TestWebhookRotationModel::create([
            'secret' => 'my_secret',
        ]);

        $payload = '{"foo": "bar"}';

        // sha256
        $sig256 = hash_hmac('sha256', $payload, 'my_secret');
        $result = $this->service->verifySignature($webhook, $payload, $sig256, 'sha256');
        expect($result['valid'])->toBeTrue();

        // sha512
        $sig512 = hash_hmac('sha512', $payload, 'my_secret');
        $result = $this->service->verifySignature($webhook, $payload, $sig512, 'sha512');
        expect($result['valid'])->toBeTrue();

        // Invalid algorithm/signature combo
        $result = $this->service->verifySignature($webhook, $payload, $sig256, 'sha512');
        expect($result['valid'])->toBeFalse();
    });

    it('normalises signatures with algorithm prefix', function () {
        $webhook = TestWebhookRotationModel::create([
            'secret' => 'my_secret',
        ]);

        $payload = '{"foo": "bar"}';
        $sig = hash_hmac('sha256', $payload, 'my_secret');
        $prefixedSig = 'sha256='.$sig;

        $result = $this->service->verifySignature($webhook, $payload, $prefixedSig, 'sha256');
        expect($result['valid'])->toBeTrue();
        expect($result['message'])->toContain('verified with current secret');
    });

    it('handles custom grace periods', function () {
        $webhook = TestWebhookRotationModel::create([
            'secret' => 'old_secret',
        ]);

        // Use a very short custom grace period
        $this->service->rotateSecret($webhook, 300); // 5 mins
        $webhook->refresh();

        expect($webhook->grace_period_seconds)->toBe(300);

        // Try to use a grace period below minimum
        $this->service->rotateSecret($webhook, 10);
        $webhook->refresh();
        expect($webhook->grace_period_seconds)->toBe(WebhookSecretRotationService::MIN_GRACE_PERIOD);

        // Try to use a grace period above maximum
        $this->service->rotateSecret($webhook, 9999999);
        $webhook->refresh();
        expect($webhook->grace_period_seconds)->toBe(WebhookSecretRotationService::MAX_GRACE_PERIOD);
    });

    it('returns correct secret status', function () {
        $now = Carbon::parse('2025-01-01 12:00:00');
        Carbon::setTestNow($now);

        $rotatedAt = $now->copy()->subHours(1);
        $webhook = TestWebhookRotationModel::create([
            'secret' => 'current',
            'previous_secret' => 'previous',
            'secret_rotated_at' => $rotatedAt,
            'grace_period_seconds' => 7200, // 2 hours
        ]);

        $status = $this->service->getSecretStatus($webhook);

        expect($status['has_previous_secret'])->toBeTrue();
        expect($status['in_grace_period'])->toBeTrue();
        expect($status['grace_period_seconds'])->toBe(7200);
        expect($status['rotated_at'])->toBe($rotatedAt->toIso8601String());
        expect($status['time_remaining_seconds'])->toBe(3600);
        expect($status['time_remaining_human'])->toBe('1 hour');

        Carbon::setTestNow();
    });

    it('invalidates previous secret immediately', function () {
        $webhook = TestWebhookRotationModel::create([
            'secret' => 'current',
            'previous_secret' => 'previous',
            'secret_rotated_at' => now(),
        ]);

        $this->service->invalidatePreviousSecret($webhook);
        $webhook->refresh();

        expect($webhook->previous_secret)->toBeNull();
        expect($webhook->secret_rotated_at)->toBeNull();
        expect($this->service->isInGracePeriod($webhook))->toBeFalse();
    });
});
