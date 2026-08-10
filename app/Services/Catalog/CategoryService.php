<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Entities\CategoryEntity;
use App\Interfaces\Catalog\CategoryServiceInterface;
use App\Interfaces\PublicCacheInvalidationNotifierInterface;
use dcardenasl\Ci4ApiCore\Localization\LocalizedTranslationStore;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;
use dcardenasl\Ci4ApiCore\Services\HasLocalizedTranslations;

/**
 * @extends BaseCrudService<CategoryEntity>
 */
class CategoryService extends BaseCrudService implements CategoryServiceInterface
{
    use HasLocalizedTranslations;

    /**
     * @param RepositoryInterface<CategoryEntity> $categoryRepository
     */
    public function __construct(
        RepositoryInterface $categoryRepository,
        ResponseMapperInterface $responseMapper,
        LocalizedTranslationStore $translationStore,
        private readonly PublicCacheInvalidationNotifierInterface $cacheInvalidator,
    ) {
        parent::__construct($categoryRepository, $responseMapper);
        $this->translationStore = $translationStore;
        $this->localizedResourceType = 'category';
    }

    protected function afterStore(object $entity, ?\dcardenasl\Ci4ApiCore\Dto\SecurityContext $context): void
    {
        parent::afterStore($entity, $context);
        $this->cacheInvalidator->invalidate(['categories', 'collection_items']);
    }

    protected function afterUpdate(object $entity, ?\dcardenasl\Ci4ApiCore\Dto\SecurityContext $context): void
    {
        parent::afterUpdate($entity, $context);
        $this->cacheInvalidator->invalidate(['categories', 'collection_items']);
    }

    protected function afterDelete(object $entity, ?\dcardenasl\Ci4ApiCore\Dto\SecurityContext $context): void
    {
        parent::afterDelete($entity, $context);
        $this->cacheInvalidator->invalidate(['categories', 'collection_items']);
    }
}
