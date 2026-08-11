<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\DTO\Response\Catalog\CategoryResponseDTO;
use App\Entities\CategoryEntity;
use App\Interfaces\Catalog\AdminListProjectionRepositoryInterface;
use App\Interfaces\Catalog\CategoryServiceInterface;
use App\Interfaces\PublicCacheInvalidationNotifierInterface;
use App\Support\AdminListProjectionDecoder;
use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use dcardenasl\Ci4ApiCore\Dto\PaginatedResponseDTO;
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
        private readonly ?AdminListProjectionRepositoryInterface $categoryListRepository = null,
    ) {
        parent::__construct($categoryRepository, $responseMapper);
        $this->translationStore = $translationStore;
        $this->localizedResourceType = 'category';
    }

    public function index(DataTransferObjectInterface $request, ?\dcardenasl\Ci4ApiCore\Dto\SecurityContext $context = null): DataTransferObjectInterface
    {
        $requestData = $request->toArray();
        if (($requestData['projection'] ?? 'full') !== 'list' || $this->categoryListRepository === null) {
            return parent::index($request, $context);
        }

        $result = $this->categoryListRepository->paginateAdminList($requestData, (int) ($requestData['page'] ?? 1), (int) ($requestData['per_page'] ?? 20));
        $data = array_map(static function (array $row): CategoryResponseDTO {
            $decoded = AdminListProjectionDecoder::translations($row['translations_data'] ?? null);
            $row['translations'] = array_map(static fn (array $translation): array => [
                'locale' => $translation['locale'],
                ...$translation['fields'],
            ], $decoded);
            $row['localized'] = array_filter([
                'name' => (string) ($row['name'] ?? ''),
                'short_description' => (string) ($row['short_description'] ?? ''),
            ], static fn (string $value): bool => $value !== '');
            unset($row['translations_data'], $row['total_items']);

            return CategoryResponseDTO::fromArray($row);
        }, $result['data']);

        return PaginatedResponseDTO::fromArray(['data' => $data, 'total' => $result['total'], 'page' => $result['page'], 'per_page' => $result['per_page']]);
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
