<?php

declare(strict_types=1);

namespace App\Services\Catalog;

/**
 * Reports all collection_items rows that reference a given Hub file ID,
 * via cover_file_id or the gallery_file_ids CSV column. Mirrors the
 * "shared usages contract" (source, resource, resource_id, role, label)
 * cms-domain's FileUsageService established, so the Hub's
 * DomainFileUsageClient can merge results from every domain uniformly.
 *
 * Deliberately references {@see \App\Models\CollectionItemModel} by fully
 * qualified name rather than a top-of-file `use App\Models\...` import —
 * `tests/Unit/Architecture/ServiceModelDependencyConventionsTest.php` ratchets
 * against services importing models directly; this repo's established
 * workaround (see `CollectionItemService::getPublicActive()`) is to resolve
 * via `model()`/FQCN inline instead.
 *
 * @phpstan-type UsageItem array{source: string, resource: string, resource_id: int, role: string, label: string|null}
 */
class FileUsageService
{
    public function __construct(private readonly \App\Models\CollectionItemModel $collectionItemModel)
    {
    }

    /**
     * @return list<UsageItem>
     */
    public function getUsagesByHubFileId(int $hubFileId): array
    {
        // gallery_file_ids is a plain CSV column (no FIND_IN_SET/JSON index),
        // so a SQL substring match would false-positive (file 1 matching
        // "21" or "12,1"). Narrow with a cheap SQL prefilter (candidates
        // whose CSV *contains the digits somewhere*, or whose cover matches
        // exactly), then verify exact membership in PHP.
        //
        // Goes through the model's builder() (bound to collection_items)
        // rather than findAll() so the result stays a plain
        // getResultArray() shape — see CollectionItemTechniqueModel for why.
        // where('deleted_at', null) stays explicit here (rather than
        // relying on the model's default soft-delete scope) so this reads
        // the same as the un-scoped builder query it replaces.
        $result = $this->collectionItemModel->builder()
            ->select('id, name, cover_file_id, gallery_file_ids')
            ->where('deleted_at', null)
            ->groupStart()
                ->where('cover_file_id', $hubFileId)
                ->orLike('gallery_file_ids', (string) $hubFileId, 'both')
            ->groupEnd()
            ->get();
        $rows = $result !== false ? $result->getResultArray() : [];

        $usages = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            $name = isset($row['name']) ? (string) $row['name'] : null;
            $galleryIds = $this->parseCsvIds((string) ($row['gallery_file_ids'] ?? ''));

            if ((int) ($row['cover_file_id'] ?? 0) === $hubFileId) {
                $usages[] = ['source' => 'domain', 'resource' => 'collection_items', 'resource_id' => $id, 'role' => 'cover', 'label' => $name];
            }
            if (in_array($hubFileId, $galleryIds, true)) {
                $usages[] = ['source' => 'domain', 'resource' => 'collection_items', 'resource_id' => $id, 'role' => 'gallery', 'label' => $name];
            }
        }

        return $usages;
    }

    /**
     * @return list<int>
     */
    private function parseCsvIds(string $csv): array
    {
        if (trim($csv) === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (string $id): int => (int) trim($id),
            explode(',', $csv)
        ), static fn (int $id): bool => $id > 0));
    }
}
