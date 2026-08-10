<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\DTO\Request\Catalog\PublicReadCollectionItemRequestDTO;
use App\Interfaces\Catalog\PublicReadCollectionItemReaderInterface;
use App\Libraries\Hub\HubClient;
use App\Modules\PublicRead\Support\PublicReadEnvelope;
use CodeIgniter\Database\BaseConnection;
use dcardenasl\Ci4ApiCore\Support\ApiResult;

/**
 * Set-based public reader for catalog collection items.
 *
 * The reader owns the public projection and never delegates to
 * CollectionItemService, whose interface is intentionally CRUD-oriented.
 */
final class PublicReadCollectionItemReader implements PublicReadCollectionItemReaderInterface
{
    private const RESOURCE_TYPE = 'collection_item';
    /** @var list<string> */
    private const PUBLIC_COLUMNS = [
        'id', 'name', 'category_id', 'inventory_code', 'status', 'summary',
        'curiosidad', 'contenido', 'origin', 'period', 'creator', 'ubicacion',
        'materials', 'cover_file_id', 'gallery_file_ids', 'collection_number',
        'collection_group', 'physical_description', 'dimensions', 'ingress_type',
        'donated_by', 'tags', 'links', 'company_history', 'is_active',
        'created_at', 'updated_at',
    ];

    /** @param BaseConnection<mixed, mixed> $db */
    public function __construct(
        private readonly BaseConnection $db,
        private readonly HubClient $hubClient,
        private readonly string $fallbackLocale = 'es',
    ) {
    }

    /** @param list<string> $fields */
    public function index(PublicReadCollectionItemRequestDTO $request, array $fields): ApiResult
    {
        $builder = $this->db->table('collection_items ci');
        $builder->select(implode(', ', array_map(static fn (string $column): string => 'ci.' . $column, $this->columnsFor($fields))));
        $builder->where('ci.is_active', 1)->where('ci.status', 'published')->where('ci.deleted_at', null);

        if ($request->categoryId !== null) {
            $builder->where('ci.category_id', $request->categoryId);
        }
        if ($request->categorySlug !== null) {
            $builder->where(
                'EXISTS (SELECT 1 FROM categories cfilter WHERE cfilter.id = ci.category_id AND cfilter.slug = ' . $this->db->escape($request->categorySlug) . ')',
                null,
                false,
            );
        }

        if ($request->techniqueId !== null) {
            $builder->where(
                'EXISTS (SELECT 1 FROM collection_item_technique cit WHERE cit.collection_item_id = ci.id AND cit.technique_id = ' . $this->db->escape($request->techniqueId) . ')',
                null,
                false,
            );
        }
        if ($request->technique !== null) {
            $techniqueSlugs = array_values(array_filter(array_map(
                static fn (string $slug): string => trim($slug),
                explode(',', $request->technique),
            )));
            if ($techniqueSlugs !== []) {
                $escapedSlugs = implode(', ', array_map(
                    fn (string $slug): string => (string) $this->db->escape($slug),
                    $techniqueSlugs,
                ));
                $builder->where(
                    'EXISTS (SELECT 1 FROM collection_item_technique citfilter JOIN techniques tfilter ON tfilter.id = citfilter.technique_id WHERE citfilter.collection_item_id = ci.id AND tfilter.slug IN (' . $escapedSlugs . ') AND tfilter.deleted_at IS NULL)',
                    null,
                    false,
                );
            }
        }

        if ($request->search !== '') {
            // Search the canonical value and both requested/fallback localized
            // names without joining the EAV table into the result set. The
            // EXISTS predicate keeps pagination and count cardinality stable.
            $escapedSearchValue = $this->db->escapeString($request->search, true);
            $escapedSearch = is_string($escapedSearchValue) ? $escapedSearchValue : '';
            $searchPattern = (string) $this->db->escape('%' . $escapedSearch . '%');
            $likeEscape = sprintf($this->db->likeEscapeStr, $this->db->likeEscapeChar);
            $locales = implode(', ', array_map(
                fn (string $locale): string => (string) $this->db->escape($locale),
                array_values(array_unique([$request->locale, $this->fallbackLocale])),
            ));

            $builder->groupStart()
                ->like('ci.name', $request->search)
                ->orWhere(
                    "EXISTS (SELECT 1 FROM catalog_translations cts WHERE cts.translatable_type = '" . self::RESOURCE_TYPE . "' AND cts.translatable_id = ci.id AND cts.locale IN ({$locales}) AND cts.field = 'name' AND cts.value LIKE {$searchPattern}{$likeEscape})",
                    null,
                    false,
                )
                ->groupEnd();
        }

        $countBuilder = clone $builder;
        $total = (int) $countBuilder->countAllResults();

        $sort = in_array($request->sort, ['name', 'created_at', 'id'], true) ? $request->sort : 'name';
        $builder->orderBy('ci.' . $sort, 'ASC')->orderBy('ci.id', 'ASC');
        $builder->limit($request->perPage, ($request->page - 1) * $request->perPage);
        $result = $builder->get();
        $rows = $result !== false ? array_values($result->getResultArray()) : [];
        $data = $this->hydrate($rows, $request->locale, false, $fields);

        return PublicReadEnvelope::success(
            locale: $request->locale,
            data: $data,
            sourceRevision: $this->revision($rows),
            page: $request->page,
            perPage: $request->perPage,
            total: $total,
            meta: ['fields' => $fields, 'query' => $request->toArray()],
        );
    }

    /** @param list<string> $fields */
    public function show(string $locale, string $idOrSlug, array $fields): ApiResult
    {
        $idOrSlug = trim($idOrSlug);
        $builder = $this->db->table('collection_items ci');
        $builder->select(implode(', ', array_map(static fn (string $column): string => 'ci.' . $column, $this->columnsFor($fields))));
        $builder->where('ci.is_active', 1)->where('ci.status', 'published')->where('ci.deleted_at', null);

        if (ctype_digit($idOrSlug)) {
            $builder->where('ci.id', (int) $idOrSlug);
        } else {
            $builder->groupStart()
                ->where('ci.inventory_code', $idOrSlug)
                ->orWhere(
                    "EXISTS (SELECT 1 FROM catalog_public_slugs ps WHERE ps.resource_type = 'collection_item' AND ps.resource_id = ci.id AND ps.locale IN (" . $this->db->escape($locale) . ', ' . $this->db->escape($this->fallbackLocale) . ") AND ps.slug = " . $this->db->escape($idOrSlug) . ')',
                    null,
                    false,
                )
                ->groupEnd();
        }

        $query = $builder->get();
        $row = $query !== false ? $query->getRowArray() : null;
        if ($row === null) {
            return new ApiResult([
                'version' => 1,
                'ok' => false,
                'data' => null,
                'meta' => ['locale' => $locale, 'source_revision' => 'catalog:empty'],
                'source' => ['domain' => 'catalog', 'state' => 'unavailable', 'stale' => false],
                'messages' => ['Collection item not found.'],
            ], 404);
        }

        $data = $this->hydrate([$row], $locale, true, $fields)[0] ?? [];

        return PublicReadEnvelope::success(
            locale: $locale,
            data: $data,
            sourceRevision: $this->revision([$row]),
            meta: ['fields' => $fields, 'query' => ['id_or_slug' => $idOrSlug]],
        );
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @param list<string> $fields
     * @return list<array<string, mixed>>
     */
    private function hydrate(array $rows, string $locale, bool $detail, array $fields): array
    {
        if ($rows === []) {
            return [];
        }

        $ids = array_values(array_unique(array_map(static fn (array $row): int => (int) $row['id'], $rows)));
        $translationMap = [];
        if ($this->needsTranslations($fields)) {
            $translationResult = $this->db->table('catalog_translations')
                ->select('translatable_id, locale, field, value')
                ->where('translatable_type', self::RESOURCE_TYPE)
                ->whereIn('translatable_id', $ids)
                ->whereIn('locale', array_values(array_unique([$locale, $this->fallbackLocale])))
                ->get();
            $translations = $translationResult !== false ? $translationResult->getResultArray() : [];

            foreach ($translations as $translation) {
                $translationMap[(int) $translation['translatable_id']][(string) $translation['locale']][(string) $translation['field']] = (string) $translation['value'];
            }
        }

        $slugMap = [];
        if ($this->needsSlugs($fields)) {
            $slugResult = $this->db->table('catalog_public_slugs')
                ->select('resource_id, locale, slug')
                ->where('resource_type', self::RESOURCE_TYPE)
                ->whereIn('resource_id', $ids)
                ->whereIn('locale', array_values(array_unique([$locale, $this->fallbackLocale])))
                ->get();
            $slugs = $slugResult !== false ? $slugResult->getResultArray() : [];

            foreach ($slugs as $slug) {
                $slugMap[(int) $slug['resource_id']][(string) $slug['locale']] = (string) $slug['slug'];
            }
        }

        $techniqueMap = [];
        if ($detail && $this->needsField($fields, 'techniques')) {
            $techniqueResult = $this->db->table('collection_item_technique cit')
                ->select('cit.collection_item_id, t.id, t.name, t.slug, t.summary')
                ->join('techniques t', 't.id = cit.technique_id', 'inner')
                ->whereIn('cit.collection_item_id', $ids)
                ->where('t.deleted_at', null)
                ->orderBy('t.sort_order', 'ASC')->orderBy('t.name', 'ASC')
                ->get();
            $techniques = $techniqueResult !== false ? $techniqueResult->getResultArray() : [];
            foreach ($techniques as $technique) {
                $techniqueMap[(int) $technique['collection_item_id']][] = [
                    'id' => (int) $technique['id'],
                    'name' => (string) $technique['name'],
                    'slug' => (string) $technique['slug'],
                    'summary' => $technique['summary'],
                ];
            }
        }

        $categoryMap = [];
        if ($this->needsField($fields, 'category')) {
            $categoryIds = array_values(array_unique(array_map(static fn (array $row): int => (int) ($row['category_id'] ?? 0), $rows)));
            $categoryResult = $categoryIds === [] ? false : $this->db->table('categories c')
                ->select('c.id, c.name, c.slug, c.short_description')
                ->whereIn('c.id', $categoryIds)->where('c.deleted_at', null)->get();
            if ($categoryResult !== false) {
                foreach ($categoryResult->getResultArray() as $category) {
                    $categoryMap[(int) $category['id']] = [
                        'id' => (int) $category['id'],
                        'name' => (string) $category['name'],
                        'slug' => (string) $category['slug'],
                        'summary' => $category['short_description'],
                    ];
                }
            }
        }

        $resolveCover = $fields === [] || in_array('cover_image', $fields, true);
        $resolveGallery = $fields === [] || in_array('gallery_images', $fields, true);
        $fileIds = [];
        foreach ($rows as $row) {
            $fileIds = array_merge($fileIds, $this->fileIds($row, $resolveCover, $resolveGallery));
        }
        $media = $this->resolveMedia($fileIds);

        $result = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $localized = $this->localized($row, $translationMap[$id] ?? [], $locale);
            $slug = $slugMap[$id][$locale] ?? $slugMap[$id][$this->fallbackLocale] ?? '';
            $payload = [
                'id' => $id,
                'name' => $localized['name'] ?? (string) ($row['name'] ?? ''),
                'category_id' => (int) ($row['category_id'] ?? 0),
                'category' => $categoryMap[(int) ($row['category_id'] ?? 0)] ?? null,
                'inventory_code' => $row['inventory_code'] ?? null,
                'status' => (string) ($row['status'] ?? ''),
                'summary' => $localized['summary'] ?? ($row['summary'] ?? null),
                'curiosidad' => $localized['curiosidad'] ?? ($row['curiosidad'] ?? null),
                'contenido' => $localized['contenido'] ?? ($row['contenido'] ?? null),
                'origin' => $row['origin'] ?? null,
                'period' => $row['period'] ?? null,
                'creator' => $row['creator'] ?? null,
                'ubicacion' => $localized['ubicacion'] ?? ($row['ubicacion'] ?? null),
                'materials' => $row['materials'] ?? null,
                'cover_file_id' => isset($row['cover_file_id']) ? (int) $row['cover_file_id'] : null,
                'gallery_file_ids' => $row['gallery_file_ids'] ?? null,
                'cover_image' => $this->mediaItem($media, (int) ($row['cover_file_id'] ?? 0)),
                'gallery_images' => $this->galleryMedia($media, $row['gallery_file_ids'] ?? null),
                'collection_number' => $row['collection_number'] ?? null,
                'collection_group' => $row['collection_group'] ?? null,
                'physical_description' => $localized['physical_description'] ?? ($row['physical_description'] ?? null),
                'dimensions' => $row['dimensions'] ?? null,
                'ingress_type' => $row['ingress_type'] ?? null,
                'donated_by' => $row['donated_by'] ?? null,
                'tags' => $row['tags'] ?? null,
                'links' => $row['links'] ?? null,
                'company_history' => $row['company_history'] ?? null,
                'is_active' => (int) ($row['is_active'] ?? 0),
                'localized' => $localized,
                'translations' => $this->translationPayload($translationMap[$id] ?? []),
                'slug' => $slug,
                'slugs' => $slugMap[$id] ?? [],
                'created_at' => $row['created_at'] ?? null,
                'updated_at' => $row['updated_at'] ?? null,
            ];

            if ($detail) {
                $payload['techniques'] = $techniqueMap[$id] ?? [];
            }

            $result[] = $this->filterFields($payload, $fields);
        }

        return $result;
    }

    /** @param list<string> $fields */
    private function needsTranslations(array $fields): bool
    {
        return $this->needsOneOf($fields, [
            'name', 'summary', 'curiosidad', 'contenido', 'ubicacion',
            'physical_description', 'localized', 'translations',
        ]);
    }

    /** @param list<string> $fields */
    private function needsSlugs(array $fields): bool
    {
        return $this->needsOneOf($fields, ['slug', 'slugs']);
    }

    /** @param list<string> $fields */
    private function needsField(array $fields, string $field): bool
    {
        return $fields === [] || in_array($field, $fields, true);
    }

    /**
     * @param list<string> $fields
     * @param list<string> $required
     */
    private function needsOneOf(array $fields, array $required): bool
    {
        return $fields === [] || array_intersect($required, $fields) !== [];
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, array<string, string>> $rows
     * @return array<string, string>
     */
    private function localized(array $row, array $rows, string $locale): array
    {
        $localized = [];
        $fields = ['name', 'summary', 'contenido', 'curiosidad', 'physical_description', 'ubicacion'];
        foreach ($fields as $field) {
            $localized[$field] = $rows[$locale][$field] ?? $rows[$this->fallbackLocale][$field] ?? (string) ($row[$field] ?? '');
        }

        return $localized;
    }

    /**
     * @param array<string, array<string, string>> $rows
     * @return list<array<string, string>>
     */
    private function translationPayload(array $rows): array
    {
        $payload = [];
        foreach ($rows as $locale => $fields) {
            $payload[] = ['locale' => $locale, ...$fields];
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<string> $fields
     * @return array<string, mixed>
     */
    private function filterFields(array $payload, array $fields): array
    {
        if ($fields === []) {
            return $payload;
        }

        return array_intersect_key($payload, array_flip($fields));
    }

    /**
     * @param array<string, mixed> $row
     * @return list<int>
     */
    private function fileIds(array $row, bool $resolveCover, bool $resolveGallery): array
    {
        $ids = [];
        if ($resolveCover && (int) ($row['cover_file_id'] ?? 0) > 0) {
            $ids[] = (int) $row['cover_file_id'];
        }
        if ($resolveGallery) {
            foreach (explode(',', (string) ($row['gallery_file_ids'] ?? '')) as $rawId) {
                if ((int) trim($rawId) > 0) {
                    $ids[] = (int) trim($rawId);
                }
            }
        }

        return array_map(static fn (mixed $id): int => (int) $id, array_values(array_unique($ids)));
    }

    /**
     * @param array<int, mixed> $ids
     * @return array<int, array<string, mixed>>
     */
    private function resolveMedia(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $normalized = array_values(array_map(static fn (mixed $id): int => (int) $id, $ids));

        return $this->hubClient->resolvePublicFileMeta($normalized);
    }

    /**
     * @param array<int, array<string, mixed>> $media
     * @return array<string, mixed>|null
     */
    private function mediaItem(array $media, int $id): ?array
    {
        if ($id <= 0 || !isset($media[$id])) {
            return null;
        }

        $meta = $media[$id];
        return [
            'source_kind' => 'hub_file',
            'file_id' => $id,
            'url' => $meta['url'] ?? null,
            'variants' => is_string($meta['variants'] ?? null) ? json_decode($meta['variants'], true) : ($meta['variants'] ?? null),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $media
     * @return list<array<string, mixed>>
     */
    private function galleryMedia(array $media, mixed $rawIds): array
    {
        $result = [];
        foreach (explode(',', (string) $rawIds) as $rawId) {
            $item = $this->mediaItem($media, (int) trim($rawId));
            if ($item !== null) {
                $result[] = $item;
            }
        }

        return $result;
    }

    /** @param list<array<string, mixed>> $rows */
    private function revision(array $rows): string
    {
        $updated = '';
        $maxId = 0;
        foreach ($rows as $row) {
            $updated = max($updated, (string) ($row['updated_at'] ?? ''));
            $maxId = max($maxId, (int) ($row['id'] ?? 0));
        }

        return 'catalog:' . ($updated !== '' ? $updated : 'empty') . ':' . $maxId;
    }

    /**
     * @param list<string> $fields
     * @return list<string>
     */
    private function columnsFor(array $fields): array
    {
        if ($fields === []) {
            return self::PUBLIC_COLUMNS;
        }

        $required = ['id', 'name', 'category_id', 'status', 'updated_at'];
        $fieldColumns = array_intersect(self::PUBLIC_COLUMNS, $fields);
        if (in_array('cover_image', $fields, true)) {
            $fieldColumns[] = 'cover_file_id';
        }
        if (in_array('gallery_images', $fields, true)) {
            $fieldColumns[] = 'gallery_file_ids';
        }
        return array_values(array_unique(array_merge($required, $fieldColumns)));
    }
}
