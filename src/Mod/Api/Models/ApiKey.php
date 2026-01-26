<?php

declare(strict_types=1);

namespace Core\Mod\Api\Models;

use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * API Key - authenticates SDK and REST API requests.
 *
 * Keys are prefixed with 'hk_' for identification.
 * The actual key is hashed using bcrypt and never stored in plain text.
 *
 * Security: Keys created before the bcrypt migration use SHA-256 (without salt).
 * The hash_algorithm column tracks which algorithm was used for each key.
 * Legacy SHA-256 keys should be rotated to use the secure bcrypt algorithm.
 */
class ApiKey extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Hash algorithm identifiers.
     */
    public const HASH_SHA256 = 'sha256';

    public const HASH_BCRYPT = 'bcrypt';

    /**
     * Default grace period for key rotation (in hours).
     */
    public const DEFAULT_GRACE_PERIOD_HOURS = 24;

    /**
     * Scopes available for API keys.
     */
    public const SCOPE_READ = 'read';

    public const SCOPE_WRITE = 'write';

    public const SCOPE_DELETE = 'delete';

    public const ALL_SCOPES = [
        self::SCOPE_READ,
        self::SCOPE_WRITE,
        self::SCOPE_DELETE,
    ];

    protected $fillable = [
        'workspace_id',
        'user_id',
        'name',
        'key',
        'hash_algorithm',
        'prefix',
        'scopes',
        'server_scopes',
        'last_used_at',
        'expires_at',
        'grace_period_ends_at',
        'rotated_from_id',
    ];

    protected $casts = [
        'scopes' => 'array',
        'server_scopes' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'grace_period_ends_at' => 'datetime',
    ];

    protected $hidden = [
        'key', // Never expose the hashed key
    ];

    /**
     * Generate a new API key for a workspace.
     *
     * Returns both the ApiKey model and the plain key (only available once).
     * New keys use bcrypt for secure hashing with salt.
     *
     * @return array{api_key: ApiKey, plain_key: string}
     */
    public static function generate(
        int $workspaceId,
        int $userId,
        string $name,
        array $scopes = [self::SCOPE_READ, self::SCOPE_WRITE],
        ?\DateTimeInterface $expiresAt = null
    ): array {
        $plainKey = Str::random(48);
        $prefix = 'hk_'.Str::random(8);

        $apiKey = static::create([
            'workspace_id' => $workspaceId,
            'user_id' => $userId,
            'name' => $name,
            'key' => Hash::make($plainKey),
            'hash_algorithm' => self::HASH_BCRYPT,
            'prefix' => $prefix,
            'scopes' => $scopes,
            'expires_at' => $expiresAt,
        ]);

        // Return plain key only once - never stored
        return [
            'api_key' => $apiKey,
            'plain_key' => "{$prefix}_{$plainKey}",
        ];
    }

    /**
     * Find an API key by its plain text value.
     *
     * Supports both legacy SHA-256 keys and new bcrypt keys.
     * For bcrypt keys, we must load all candidates by prefix and verify each.
     */
    public static function findByPlainKey(string $plainKey): ?static
    {
        // Expected format: hk_xxxxxxxx_xxxxx...
        if (! str_starts_with($plainKey, 'hk_')) {
            return null;
        }

        $parts = explode('_', $plainKey, 3);
        if (count($parts) !== 3) {
            return null;
        }

        $prefix = $parts[0].'_'.$parts[1]; // hk_xxxxxxxx
        $key = $parts[2];

        // Find potential matches by prefix
        $candidates = static::where('prefix', $prefix)
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->where(function ($query) {
                // Exclude keys past their grace period
                $query->whereNull('grace_period_ends_at')
                    ->orWhere('grace_period_ends_at', '>', now());
            })
            ->get();

        foreach ($candidates as $candidate) {
            if ($candidate->verifyKey($key)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Verify if the provided key matches this API key's stored hash.
     *
     * Handles both legacy SHA-256 and secure bcrypt algorithms.
     */
    public function verifyKey(string $plainKey): bool
    {
        if ($this->hash_algorithm === self::HASH_BCRYPT) {
            return Hash::check($plainKey, $this->key);
        }

        // Legacy SHA-256 verification (for backward compatibility)
        return hash_equals($this->key, hash('sha256', $plainKey));
    }

    /**
     * Check if this key uses legacy (insecure) SHA-256 hashing.
     *
     * Keys using SHA-256 should be rotated to use bcrypt.
     */
    public function usesLegacyHash(): bool
    {
        return $this->hash_algorithm === self::HASH_SHA256
            || $this->hash_algorithm === null;
    }

    /**
     * Rotate this API key, creating a new secure key.
     *
     * The old key remains valid during the grace period to allow
     * seamless migration of integrations.
     *
     * @param  int  $gracePeriodHours  Hours the old key remains valid
     * @return array{api_key: ApiKey, plain_key: string, old_key: ApiKey}
     */
    public function rotate(int $gracePeriodHours = self::DEFAULT_GRACE_PERIOD_HOURS): array
    {
        // Create new key with same settings
        $result = static::generate(
            $this->workspace_id,
            $this->user_id,
            $this->name,
            $this->scopes ?? [self::SCOPE_READ, self::SCOPE_WRITE],
            $this->expires_at
        );

        // Copy server scopes to new key
        $result['api_key']->update([
            'server_scopes' => $this->server_scopes,
            'rotated_from_id' => $this->id,
        ]);

        // Set grace period on old key
        $this->update([
            'grace_period_ends_at' => now()->addHours($gracePeriodHours),
        ]);

        return [
            'api_key' => $result['api_key'],
            'plain_key' => $result['plain_key'],
            'old_key' => $this,
        ];
    }

    /**
     * Check if this key is currently in a rotation grace period.
     */
    public function isInGracePeriod(): bool
    {
        return $this->grace_period_ends_at !== null
            && $this->grace_period_ends_at->isFuture();
    }

    /**
     * Check if the grace period has expired (key should be revoked).
     */
    public function isGracePeriodExpired(): bool
    {
        return $this->grace_period_ends_at !== null
            && $this->grace_period_ends_at->isPast();
    }

    /**
     * End the grace period early and revoke this key.
     */
    public function endGracePeriod(): void
    {
        $this->update(['grace_period_ends_at' => now()]);
        $this->revoke();
    }

    /**
     * Record API key usage.
     */
    public function recordUsage(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Check if key has a specific scope.
     */
    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes ?? [], true);
    }

    /**
     * Check if key has all specified scopes.
     */
    public function hasScopes(array $scopes): bool
    {
        foreach ($scopes as $scope) {
            if (! $this->hasScope($scope)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if key is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Check if key has access to a specific MCP server.
     */
    public function hasServerAccess(string $serverId): bool
    {
        // Null means all servers
        if ($this->server_scopes === null) {
            return true;
        }

        return in_array($serverId, $this->server_scopes, true);
    }

    /**
     * Get list of allowed servers (null = all).
     */
    public function getAllowedServers(): ?array
    {
        return $this->server_scopes;
    }

    /**
     * Revoke this API key.
     */
    public function revoke(): void
    {
        $this->delete();
    }

    /**
     * Get the masked key for display.
     * Shows prefix and last 4 characters.
     */
    public function getMaskedKeyAttribute(): string
    {
        return "{$this->prefix}_****";
    }

    // Relationships
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the key this one was rotated from.
     */
    public function rotatedFrom(): BelongsTo
    {
        return $this->belongsTo(static::class, 'rotated_from_id');
    }

    // Query Scopes
    public function scopeForWorkspace($query, int $workspaceId)
    {
        return $query->where('workspace_id', $workspaceId);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('grace_period_ends_at')
                    ->orWhere('grace_period_ends_at', '>', now());
            });
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    /**
     * Keys currently in a rotation grace period.
     */
    public function scopeInGracePeriod($query)
    {
        return $query->whereNotNull('grace_period_ends_at')
            ->where('grace_period_ends_at', '>', now());
    }

    /**
     * Keys with expired grace periods (should be cleaned up).
     */
    public function scopeGracePeriodExpired($query)
    {
        return $query->whereNotNull('grace_period_ends_at')
            ->where('grace_period_ends_at', '<=', now());
    }

    /**
     * Keys using legacy SHA-256 hashing (should be rotated).
     */
    public function scopeLegacyHash($query)
    {
        return $query->where(function ($q) {
            $q->where('hash_algorithm', self::HASH_SHA256)
                ->orWhereNull('hash_algorithm');
        });
    }

    /**
     * Keys using secure bcrypt hashing.
     */
    public function scopeSecureHash($query)
    {
        return $query->where('hash_algorithm', self::HASH_BCRYPT);
    }
}
