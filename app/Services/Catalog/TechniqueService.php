<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\DTO\Response\Catalog\TechniqueResponseDTO;
use App\Entities\TechniqueEntity;
use App\Interfaces\Catalog\AdminListProjectionRepositoryInterface;
use App\Interfaces\Catalog\TechniqueServiceInterface;
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
 * @extends BaseCrudService<TechniqueEntity>
 */
class TechniqueService extends BaseCrudService implements TechniqueServiceInterface
{
    use HasLocalizedTranslations;

    /**
     * @param RepositoryInterface<TechniqueEntity> $techniqueRepository
     */
    public function __construct(
        RepositoryInterface $techniqueRepository,
        ResponseMapperInterface $responseMapper,
        LocalizedTranslationStore $translationStore,
        private readonly PublicCacheInvalidationNotifierInterface $cacheInvalidator,
        private readonly ?AdminListProjectionRepositoryInterface $techniqueListRepository = null,
    ) {
        parent::__construct($techniqueRepository, $responseMapper);
        $this->translationStore = $translationStore;
        $this->localizedResourceType = 'technique';
    }

    public function index(DataTransferObjectInterface $request, ?\dcardenasl\Ci4ApiCore\Dto\SecurityContext $context = null): DataTransferObjectInterface
    {
        $requestData = $request->toArray();
        if (($requestData['projection'] ?? 'full') !== 'list' || $this->techniqueListRepository === null) {
            return parent::index($request, $context);
        }

        $result = $this->techniqueListRepository->paginateAdminList($requestData, (int) ($requestData['page'] ?? 1), (int) ($requestData['per_page'] ?? 20));
        $data = array_map(static function (array $row): TechniqueResponseDTO {
            $decoded = AdminListProjectionDecoder::translations($row['translations_data'] ?? null);
            $row['translations'] = array_map(static fn (array $translation): array => [
                'locale' => $translation['locale'],
                ...$translation['fields'],
            ], $decoded);
            $row['localized'] = array_filter([
                'name' => (string) ($row['name'] ?? ''),
                'summary' => (string) ($row['summary'] ?? ''),
            ], static fn (string $value): bool => $value !== '');
            unset($row['translations_data'], $row['total_items']);

            return TechniqueResponseDTO::fromArray($row);
        }, $result['data']);

        return PaginatedResponseDTO::fromArray(['data' => $data, 'total' => $result['total'], 'page' => $result['page'], 'per_page' => $result['per_page']]);
    }

    protected function afterStore(object $entity, ?\dcardenasl\Ci4ApiCore\Dto\SecurityContext $context): void
    {
        parent::afterStore($entity, $context);
        $this->cacheInvalidator->invalidate(['techniques', 'collection_items']);
    }

    protected function afterUpdate(object $entity, ?\dcardenasl\Ci4ApiCore\Dto\SecurityContext $context): void
    {
        parent::afterUpdate($entity, $context);
        $this->cacheInvalidator->invalidate(['techniques', 'collection_items']);
    }

    protected function afterDelete(object $entity, ?\dcardenasl\Ci4ApiCore\Dto\SecurityContext $context): void
    {
        parent::afterDelete($entity, $context);
        $this->cacheInvalidator->invalidate(['techniques', 'collection_items']);
    }

    /**
     * Public detail lookup by numeric id or the technique's own slug column.
     *
     * @return array<string, mixed>
     */
    public function getPublic(string $idOrSlug): array
    {
        $model = model(\App\Models\TechniqueModel::class);
        $entity = is_numeric($idOrSlug) ? $model->find((int) $idOrSlug) : $model->where('slug', $idOrSlug)->first();
        if (!$entity) {
            throw new \dcardenasl\Ci4ApiCore\Exceptions\NotFoundException(lang('Techniques.not_found'));
        }

        $enriched = $this->enrichEntities([$entity]);

        return $this->mapToResponse($enriched[0] ?? $entity)->toArray();
    }
}
