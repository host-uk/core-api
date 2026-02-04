<?php

declare(strict_types=1);

namespace Core\Api\Policies;

use Core\Tenant\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class WebhookPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can update the webhook.
     *
     * Only workspace owners and admins can manage webhook secrets
     * and rotation settings.
     *
     * @param  User  $user
     * @param  mixed  $webhook  Social Webhook or Content Webhook Endpoint
     * @return bool
     */
    public function update(User $user, mixed $webhook): bool
    {
        return $user->workspaces()
            ->where('workspaces.id', $webhook->workspace_id)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }
}
