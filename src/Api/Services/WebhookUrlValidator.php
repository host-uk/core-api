<?php

declare(strict_types=1);

namespace Core\Api\Services;

use Illuminate\Support\Facades\Log;

/**
 * Webhook URL Validator.
 *
 * Validates webhook URLs to prevent Server-Side Request Forgery (SSRF).
 * Blocks private/reserved IP ranges, localhost, and cloud metadata endpoints.
 */
class WebhookUrlValidator
{
    /**
     * Blocked hostnames.
     */
    private const BLOCKED_HOSTS = [
        'localhost',
        'localhost.localdomain',
    ];

    /**
     * Validate a webhook URL.
     *
     * @param  string  $url  The URL to validate
     * @return bool True if the URL is safe and valid
     */
    public function validate(string $url): bool
    {
        $parsed = parse_url($url);

        if ($parsed === false || ! isset($parsed['host']) || ! isset($parsed['scheme'])) {
            return false;
        }

        // Only allow HTTP and HTTPS
        if (! in_array(strtolower($parsed['scheme']), ['http', 'https'], true)) {
            return false;
        }

        $host = $parsed['host'];

        // Block common localhost names
        if (in_array(strtolower($host), self::BLOCKED_HOSTS, true)) {
            return false;
        }

        // Resolve DNS and validate all returned IPs
        $ips = $this->resolveHost($host);

        if (empty($ips)) {
            // If it can't be resolved, it might be an IP already or an invalid host
            if (filter_var($host, FILTER_VALIDATE_IP)) {
                $ips = [$host];
            } else {
                // If we can't resolve it and it's not an IP, we block it for safety
                // in case of intermittent DNS issues during creation.
                return false;
            }
        }

        foreach ($ips as $ip) {
            if ($this->isPrivateOrReservedIp($ip)) {
                Log::warning('Blocked restricted webhook URL resolution', [
                    'url' => $url,
                    'host' => $host,
                    'resolved_ip' => $ip,
                ]);

                return false;
            }
        }

        return true;
    }

    /**
     * Resolve a hostname to its IP addresses (IPv4 and IPv6).
     *
     * @return array<string>
     */
    protected function resolveHost(string $host): array
    {
        $ips = [];

        // IPv4 resolution
        $ipv4s = gethostbynamel($host);
        if ($ipv4s !== false) {
            $ips = array_merge($ips, $ipv4s);
        }

        // IPv6 resolution (if dns_get_record is available)
        if (function_exists('dns_get_record')) {
            try {
                $ipv6s = @dns_get_record($host, DNS_AAAA);
                if ($ipv6s) {
                    foreach ($ipv6s as $record) {
                        if (isset($record['ipv6'])) {
                            $ips[] = $record['ipv6'];
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Ignore DNS errors, rely on IPv4 or IP-based check
            }
        }

        return array_unique($ips);
    }

    /**
     * Check if an IP is private or reserved.
     */
    protected function isPrivateOrReservedIp(string $ip): bool
    {
        // FILTER_FLAG_NO_PRIV_RANGE:
        // Blocks 10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16, and 127.0.0.0/8
        // FILTER_FLAG_NO_RES_RANGE:
        // Blocks reserved ranges including 169.254.0.0/16 (Link-local/Metadata)

        $isPublic = filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );

        if ($isPublic === false) {
            return true;
        }

        // Additional manual checks for safety
        $lowIp = strtolower($ip);

        // IPv6 loopback
        if ($lowIp === '::1' || $lowIp === '0000:0000:0000:0000:0000:0000:0000:0001') {
            return true;
        }

        // Check for 169.254.x.x (Link-local / AWS Metadata) - double check
        if (str_starts_with($ip, '169.254.')) {
            return true;
        }

        // Check for IPv6 link-local (fe80::/10)
        if (str_starts_with($lowIp, 'fe80:')) {
            return true;
        }

        return false;
    }
}
