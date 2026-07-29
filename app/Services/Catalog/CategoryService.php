<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Entities\CategoryEntity;
use App\Interfaces\Catalog\CategoryServiceInterface;
use App\Libraries\Localization\LocalizedTranslationStore;
use App\Traits\Services\HasLocalizedTranslations;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

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
    ) {
        parent::__construct($categoryRepository, $responseMapper);
        $this->translationStore = $translationStore;
        $this->localizedResourceType = 'category';
    }
}
