<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Entities\CollectionItemEntity;
use App\Interfaces\Catalog\CollectionItemServiceInterface;
use App\Interfaces\PublicCacheInvalidationNotifierInterface;
use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Localization\LocalizedTranslationStore;
use dcardenasl\Ci4ApiCore\Localization\PublicSlugStore;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;
use dcardenasl\Ci4ApiCore\Services\HasLocalizedTranslations;
use dcardenasl\Ci4ApiCore\Services\HasPublicSlugs;

/**
 * @extends BaseCrudService<CollectionItemEntity>
 */
class CollectionItemService extends BaseCrudService implements CollectionItemServiceInterface
{
    use HasLocalizedTranslations {
        beforeStore as private localizedBeforeStore;
        afterStore as private localizedAfterStore;
        beforeUpdate as private localizedBeforeUpdate;
        afterUpdate as private localizedAfterUpdate;
        enrichEntities as private localizedEnrichEntities;
        mapToResponse as private localizedMapToResponse;
    }
    use HasPublicSlugs;

    /**
     * @param RepositoryInterface<CollectionItemEntity> $collectionItemRepository
     */
    public function __construct(
        RepositoryInterface $collectionItemRepository,
        ResponseMapperInterface $responseMapper,
        LocalizedTranslationStore $translationStore,
        PublicSlugStore $slugStore,
        private readonly PublicCacheInvalidationNotifierInterface $cacheInvalidator,
    ) {
        parent::__construct($collectionItemRepository, $responseMapper);
        $this->translationStore = $translationStore;
        $this->localizedResourceType = 'collection_item';
        $this->slugStore = $slugStore;
        $this->slugResourceType = 'collection_item';
        $this->slugSourceField = 'name';
    }

    /**
     * Public detail lookup: numeric id, legacy inventory code, or a
     * per-locale routing slug. Only active items are visible through here.
     *
     * @return array<string, mixed>
     */
    public function getPublicActive(string $idOrCode): array
    {
        $idOrCode = trim($idOrCode);
        $model = model(\App\Models\CollectionItemModel::class);

        if (is_numeric($idOrCode)) {
            $entity = $model->where('is_active', 1)->find((int) $idOrCode);
        } else {
            $entity = $model->where('is_active', 1)->where('inventory_code', $idOrCode)->first();

            if (! $entity) {
                $itemId = $this->slugStore->resolveResourceId('collection_item', $idOrCode);
                if ($itemId !== null) {
                    $entity = $model->where('is_active', 1)->find($itemId);
                }
            }
        }

        if (! $entity) {
            throw new \dcardenasl\Ci4ApiCore\Exceptions\NotFoundException(lang('CollectionItems.not_found'));
        }

        $enriched = $this->enrichEntities([$entity]);
        $data = $this->mapToResponse($enriched[0] ?? $entity)->toArray();

        // Fetch associated techniques
        $techniques = model(\App\Models\CollectionItemTechniqueModel::class)
            ->findTechniquesForCollectionItem((int) $entity->id);
        $techniqueIds = array_values(array_filter(array_map(
            static fn (array $technique): int => (int) ($technique['id'] ?? 0),
            $techniques,
        ), static fn (int $id): bool => $id > 0));
        $translations = $this->translationStore->forResources('technique', $techniqueIds);

        foreach ($techniques as $index => $technique) {
            $techniqueId = (int) ($technique['id'] ?? 0);
            $rows = $translations[$techniqueId] ?? [];
            if ($rows === []) {
                $this->translationStore->appendLegacyRow('technique', $rows, $technique);
            }

            $techniques[$index]['localized'] = $this->translationStore->resolve(
                'technique',
                $rows,
                $technique,
            );
        }

        $data['techniques'] = $techniques;
        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function beforeStore(array $data, ?SecurityContext $context): array
    {
        $this->pendingManualSlugs = $this->extractManualSlugs($data);

        return $this->localizedBeforeStore($data, $context);
    }

    protected function afterStore(object $entity, ?SecurityContext $context): void
    {
        $this->localizedAfterStore($entity, $context);
        $this->syncPublicSlugs($entity);
        $this->cacheInvalidator->invalidate(['collection_items']);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function beforeUpdate(int $id, array $data, ?SecurityContext $context): array
    {
        $this->pendingManualSlugs = $this->extractManualSlugs($data);

        return $this->localizedBeforeUpdate($id, $data, $context);
    }

    protected function afterUpdate(object $entity, ?SecurityContext $context): void
    {
        $this->localizedAfterUpdate($entity, $context);
        $this->syncPublicSlugs($entity);
        $this->cacheInvalidator->invalidate(['collection_items']);
    }

    protected function afterDelete(object $entity, ?SecurityContext $context): void
    {
        parent::afterDelete($entity, $context);
        $this->cacheInvalidator->invalidate(['collection_items']);
    }

    /**
     * @param array<int, object> $entities
     * @return array<int, object>
     */
    protected function enrichEntities(array $entities): array
    {
        return $this->attachSlugs($this->localizedEnrichEntities($entities));
    }

    protected function mapToResponse(object $entity): DataTransferObjectInterface
    {
        $this->attachSlugsToEntity($entity);

        return $this->localizedMapToResponse($entity);
    }
}
