<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Libraries\Hub\HubClient;

/**
 * Resolves the Hub file IDs referenced by a public collection-item payload
 * (`cover_file_id`, `gallery_file_ids`) into full Hub file metadata
 * (url + variants), via {@see HubClient::resolvePublicFileMeta()}.
 *
 * Extracted from `PublicCollectionItemController::resolveMediaFields()` —
 * controllers must not reach out to external HTTP clients directly
 * (LAYER-01); that is a Service's job. Mirrors the shape of the sibling
 * {@see FileUsageService}: a small, focused utility service with a single
 * collaborator, no CRUD interface, wired as a concrete class return in
 * `CatalogDomainServices`.
 *
 * `event-domain`'s `PublicEventController::resolveMediaFields()` had the
 * same duplicated logic and was already extracted into
 * `App\Services\Events\EventMediaResolutionService` — this class mirrors
 * that shape for cross-app consistency (see this repo's TASKS.md LAYER-01
 * entry).
 */
class CollectionItemMediaResolutionService
{
    public function __construct(private readonly HubClient $hubClient)
    {
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public function resolveMediaFields(array $item): array
    {
        $fileIds = [];
        if (isset($item['cover_file_id']) && (int) $item['cover_file_id'] > 0) {
            $fileIds[] = (int) $item['cover_file_id'];
        }

        $galleryIds = [];
        if (isset($item['gallery_file_ids']) && is_string($item['gallery_file_ids']) && trim($item['gallery_file_ids']) !== '') {
            $rawIds = explode(',', $item['gallery_file_ids']);
            foreach ($rawIds as $rawId) {
                $id = (int) trim($rawId);
                if ($id > 0) {
                    $fileIds[] = $id;
                    $galleryIds[] = $id;
                }
            }
        }

        $metaMap = [];
        if (!empty($fileIds)) {
            $metaMap = $this->hubClient->resolvePublicFileMeta($fileIds);
        }

        $item['cover_image'] = null;
        if (isset($item['cover_file_id']) && (int) $item['cover_file_id'] > 0) {
            $fileId = (int) $item['cover_file_id'];
            $meta = $metaMap[$fileId] ?? null;
            if ($meta) {
                $item['cover_image'] = [
                    'source_kind' => 'hub_file',
                    'file_id'     => $fileId,
                    'url'         => $meta['url'] ?? null,
                    'variants'    => is_string($meta['variants'] ?? null) ? json_decode($meta['variants'], true) : ($meta['variants'] ?? null),
                ];
            }
        }

        $gallery = [];
        foreach ($galleryIds as $fileId) {
            $meta = $metaMap[$fileId] ?? null;
            if ($meta) {
                $gallery[] = [
                    'source_kind' => 'hub_file',
                    'file_id'     => $fileId,
                    'url'         => $meta['url'] ?? null,
                    'variants'    => is_string($meta['variants'] ?? null) ? json_decode($meta['variants'], true) : ($meta['variants'] ?? null),
                ];
            }
        }
        $item['gallery_images'] = $gallery;

        return $item;
    }
}
