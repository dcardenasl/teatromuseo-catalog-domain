<?php

declare(strict_types=1);

namespace App\Modules\PublicRead\Support;

use DateTimeImmutable;
use DateTimeZone;
use dcardenasl\Ci4ApiCore\Support\ApiResult;

/**
 * Canonical response envelope for the PublicRead surface.
 *
 * This is deliberately separate from ApiResponse: the legacy CRUD envelope is
 * part of the compatibility contract and cannot describe source state or
 * snapshot revisions.
 */
final class PublicReadEnvelope
{
    /**
     * @param array<int|string, mixed> $data
     * @param array<string, mixed> $meta
     * @param array<string, mixed> $source
     */
    public static function success(
        string $locale,
        array $data,
        string $sourceRevision,
        ?int $page = null,
        ?int $perPage = null,
        ?int $total = null,
        array $meta = [],
        string $domain = 'catalog',
    ): ApiResult {
        return new ApiResult([
            'version' => 1,
            'ok' => true,
            'data' => $data,
            'meta' => array_merge([
                'locale' => $locale,
                'source_revision' => $sourceRevision,
                'snapshot_revision' => null,
                'fields' => [],
                'generated_at' => self::now(),
                'expires_at' => null,
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
            ], $meta),
            'source' => [
                'domain' => $domain,
                'state' => 'fresh',
                'stale' => false,
            ],
            'messages' => [],
        ]);
    }

    /** @param array<string, mixed> $meta */
    public static function unavailable(
        string $locale,
        string $message,
        string $sourceRevision,
        array $meta = [],
        string $domain = 'catalog',
    ): ApiResult {
        return new ApiResult([
            'version' => 1,
            'ok' => false,
            'data' => null,
            'meta' => array_merge([
                'locale' => $locale,
                'source_revision' => $sourceRevision,
                'snapshot_revision' => null,
                'fields' => [],
                'generated_at' => self::now(),
                'expires_at' => null,
                'page' => null,
                'per_page' => null,
                'total' => null,
            ], $meta),
            'source' => [
                'domain' => $domain,
                'state' => 'unavailable',
                'stale' => false,
            ],
            'messages' => [$message],
        ], 503);
    }

    private static function now(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DATE_ATOM);
    }
}
