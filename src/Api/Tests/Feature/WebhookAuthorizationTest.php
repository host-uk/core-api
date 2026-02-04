<?php

declare(strict_types=1);

namespace Core\Api\Tests\Feature;

use Core\Social\Models\Webhook;
use Core\Tenant\Models\User;
use Core\Tenant\Models\Workspace;
use Illuminate\Support\Str;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// Skip if models don't exist in local sandbox environment
if (class_exists('Core\Tenant\Models\Workspace')) {
    beforeEach(function () {
        $this->workspace = Workspace::factory()->create();
        $this->webhook = Webhook::factory()->create([
            'workspace_id' => $this->workspace->id,
            'uuid' => Str::uuid()->toString(),
        ]);

        // Create users with different roles
        $this->owner = User::factory()->create();
        $this->workspace->users()->attach($this->owner->id, ['role' => 'owner']);

        $this->admin = User::factory()->create();
        $this->workspace->users()->attach($this->admin->id, ['role' => 'admin']);

        $this->member = User::factory()->create();
        $this->workspace->users()->attach($this->member->id, ['role' => 'member']);
    });

    describe('Webhook Authorization', function () {
        it('allows owner to rotate social secret', function () {
            $this->actingAs($this->owner)
                ->postJson("/api/webhooks/social/{$this->webhook->uuid}/rotate")
                ->assertOk();
        });

        it('denies member from rotating social secret', function () {
            $this->actingAs($this->member)
                ->postJson("/api/webhooks/social/{$this->webhook->uuid}/rotate")
                ->assertStatus(403);
        });
    });
}
