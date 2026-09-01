<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Cache\CacheInterface;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\BaseResult;

/**
 * Collects safe, read-only diagnostics for the public-read capacity audit.
 *
 * Provider-level PHP-FPM and cache topology details are reported as unavailable
 * when the hosting runtime does not expose them. The service never returns
 * credentials, database names, hostnames, SQL text, or exception messages.
 */
final class PublicReadDiagnosticsService
{
    /**
     * @param BaseConnection<mixed, mixed> $database Application database connection.
     */
    public function __construct(
        private readonly BaseConnection $database,
        private readonly CacheInterface $cache,
    ) {
    }

    /** @return array<string, mixed> */
    public function report(): array
    {
        return [
            'schema'        => 'public-read-diagnostics.v1',
            'generated_at'  => gmdate('c'),
            'application'   => $this->runtime(),
            'database'      => $this->database(),
            'cache'         => $this->cache(),
            'content'       => $this->content(),
        ];
    }

    /** @return array<string, mixed> */
    private function runtime(): array
    {
        $load = function_exists('sys_getloadavg') ? sys_getloadavg() : false;

        return [
            'php_version'        => PHP_VERSION,
            'sapi'               => PHP_SAPI,
            'memory_limit'       => (string) ini_get('memory_limit'),
            'memory_usage_bytes' => memory_get_usage(true),
            'peak_memory_bytes'  => memory_get_peak_usage(true),
            'max_execution_time' => (int) ini_get('max_execution_time'),
            'load_average'       => is_array($load) ? $load : null,
            'extensions'         => $this->loadedExtensions(),
            'fpm'                => $this->fpmStatus(),
        ];
    }

    /** @return list<string> */
    private function loadedExtensions(): array
    {
        $known = ['curl', 'intl', 'mysqli', 'opcache', 'pdo', 'redis', 'memcached'];

        return array_values(array_filter(
            $known,
            static fn (string $extension): bool => extension_loaded($extension),
        ));
    }

    /** @return array<string, mixed> */
    private function fpmStatus(): array
    {
        if (! function_exists('fpm_get_status')) {
            return [
                'status' => 'unavailable',
                'reason' => 'fpm_get_status_not_available',
            ];
        }

        $raw = call_user_func('fpm_get_status');
        if (! is_array($raw)) {
            return [
                'status' => 'unavailable',
                'reason' => 'fpm_status_not_enabled',
            ];
        }

        $keys = [
            'accepted_conn',
            'listen_queue',
            'active_processes',
            'total_processes',
            'max_active_processes',
            'max_children_reached',
            'slow_requests',
        ];
        $metrics = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $raw)) {
                $metrics[$key] = is_numeric($raw[$key]) ? (int) $raw[$key] : $raw[$key];
            }
        }

        return [
            'status'  => 'available',
            'metrics' => $metrics,
        ];
    }

    /** @return array<string, mixed> */
    private function database(): array
    {
        $startedAt = hrtime(true);

        try {
            $versionRow = $this->queryResult(
                'SELECT VERSION() AS server_version, @@max_connections AS max_connections',
            )
                ->getRowArray();
            $statusRows = $this->queryResult(
                "SHOW GLOBAL STATUS WHERE Variable_name IN ('Aborted_connects','Connections','Max_used_connections','Questions','Slow_queries','Threads_cached','Threads_connected','Threads_created','Threads_running','Uptime')",
            )
                ->getResultArray();

            $status = [];
            foreach ($statusRows as $row) {
                $name = (string) ($row['Variable_name'] ?? '');
                if ($name !== '') {
                    $status[$name] = (int) ($row['Value'] ?? 0);
                }
            }

            $maxConnections = (int) ($versionRow['max_connections'] ?? 0);
            $currentConnections = $status['Threads_connected'] ?? 0;
            $maxUsed = $status['Max_used_connections'] ?? 0;
            $capacityStatus = match (true) {
                $maxConnections <= 0 => 'unknown',
                $currentConnections >= $maxConnections => 'critical',
                $currentConnections >= (int) ceil($maxConnections * 0.9) => 'degraded',
                default => 'healthy',
            };
            $peakReached = $maxConnections > 0 && $maxUsed >= $maxConnections;

            return [
                'status'                       => $capacityStatus,
                'response_time_ms'             => $this->elapsedMilliseconds($startedAt),
                'server_version'               => (string) ($versionRow['server_version'] ?? 'unknown'),
                'max_connections'              => $maxConnections,
                'current_connections'          => $currentConnections,
                'max_used_connections'         => $maxUsed,
                'connection_utilization_pct'   => $maxConnections > 0
                    ? round(($currentConnections / $maxConnections) * 100, 2)
                    : null,
                'current_connection_utilization_pct' => $maxConnections > 0
                    ? round(($currentConnections / $maxConnections) * 100, 2)
                    : null,
                'peak_connection_utilization_pct' => $maxConnections > 0
                    ? round(($maxUsed / $maxConnections) * 100, 2)
                    : null,
                'global_status'                => $status,
                'metrics_available'            => true,
                'historical_peak'              => $peakReached ? 'limit_reached' : 'below_limit',
                'capacity_reason'              => $capacityStatus === 'critical'
                    ? 'current_connections_at_limit'
                    : ($capacityStatus === 'degraded' ? 'current_connections_near_limit' : null),
            ];
        } catch (\Throwable) {
            return [
                'status'             => 'degraded',
                'response_time_ms'   => $this->elapsedMilliseconds($startedAt),
                'metrics_available'  => false,
                'reason'             => 'database_metrics_unavailable',
            ];
        }
    }

    /** @return BaseResult<mixed, mixed> */
    private function queryResult(string $sql): BaseResult
    {
        $result = $this->database->query($sql);

        if (! $result instanceof BaseResult) {
            throw new \RuntimeException(lang('Health.diagnosticsQueryNoResult'));
        }

        return $result;
    }

    /** @return array<string, mixed> */
    private function cache(): array
    {
        try {
            $key = 'public_read_diagnostics_probe_' . bin2hex(random_bytes(8));
            $saved = $this->cache->save($key, 'ok', 5);
            $read = $this->cache->get($key) === 'ok';
            $deleted = $this->cache->delete($key);

            return [
                'configured_handler' => (string) config('Cache')->handler,
                'active_handler'     => get_class($this->cache),
                'probe'              => $saved && $read && $deleted ? 'passed' : 'degraded',
                'topology'           => 'not_verifiable_from_application',
            ];
        } catch (\Throwable) {
            return [
                'configured_handler' => (string) config('Cache')->handler,
                'active_handler'     => get_class($this->cache),
                'probe'              => 'failed',
                'topology'           => 'not_verifiable_from_application',
            ];
        }
    }

    /**
     * Return a compact content-integrity view without exposing any records.
     *
     * These counts are intentionally restricted to the diagnostics endpoint;
     * public listing requests never pay for this audit. A zero public count is
     * distinguishable from a database-capacity problem, which is essential
     * when an empty catalog is observed through the website.
     *
     * @return array<string, mixed>
     */
    private function content(): array
    {
        try {
            $row = $this->queryResult(
                "SELECT COUNT(*) AS total,\n"
                . "SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active,\n"
                . "SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) AS published,\n"
                . "SUM(CASE WHEN is_active = 1 AND status = 'published' AND deleted_at IS NULL THEN 1 ELSE 0 END) AS public_rows,\n"
                . "SUM(CASE WHEN is_active = 1 AND status = 'published' AND deleted_at IS NULL\n"
                . "AND ((cover_file_id IS NOT NULL AND cover_file_id > 0) OR COALESCE(TRIM(gallery_file_ids), '') <> '')\n"
                . "THEN 1 ELSE 0 END) AS public_rows_with_media\n"
                . "FROM collection_items",
            )->getRowArray();

            $publicRows = (int) ($row['public_rows'] ?? 0);
            $localizedNames = (int) ($this->queryResult(
                "SELECT COUNT(DISTINCT ci.id) AS total\n"
                . "FROM collection_items ci\n"
                . "INNER JOIN catalog_translations ct ON ct.translatable_type = 'collection_item'\n"
                . "AND ct.translatable_id = ci.id AND ct.locale = 'es' AND ct.field = 'name'\n"
                . "WHERE ci.is_active = 1 AND ci.status = 'published' AND ci.deleted_at IS NULL\n"
                . "AND TRIM(ct.value) <> ''",
            )->getRowArray()['total'] ?? 0);
            $localizedSlugs = (int) ($this->queryResult(
                "SELECT COUNT(DISTINCT ci.id) AS total\n"
                . "FROM collection_items ci\n"
                . "INNER JOIN catalog_public_slugs ps ON ps.resource_type = 'collection_item'\n"
                . "AND ps.resource_id = ci.id AND ps.locale = 'es'\n"
                . "WHERE ci.is_active = 1 AND ci.status = 'published' AND ci.deleted_at IS NULL\n"
                . "AND TRIM(ps.slug) <> ''",
            )->getRowArray()['total'] ?? 0);
            $categories = (int) ($this->queryResult(
                "SELECT COUNT(*) AS total FROM categories WHERE deleted_at IS NULL",
            )->getRowArray()['total'] ?? 0);

            return [
                'status' => $publicRows > 0 ? 'healthy' : 'empty',
                'reason' => $publicRows > 0 ? null : 'no_public_collection_items',
                'collection_items' => [
                    'total'                 => (int) ($row['total'] ?? 0),
                    'active'                => (int) ($row['active'] ?? 0),
                    'published'             => (int) ($row['published'] ?? 0),
                    'public'                => $publicRows,
                    'public_with_es_name'   => $localizedNames,
                    'public_with_es_slug'   => $localizedSlugs,
                    'public_with_media'     => (int) ($row['public_rows_with_media'] ?? 0),
                ],
                'categories' => ['active' => $categories],
                'metrics_available' => true,
            ];
        } catch (\Throwable) {
            return [
                'status'            => 'unavailable',
                'metrics_available' => false,
                'reason'            => 'content_metrics_unavailable',
            ];
        }
    }

    private function elapsedMilliseconds(int $startedAt): float
    {
        return round((hrtime(true) - $startedAt) / 1_000_000, 2);
    }
}
