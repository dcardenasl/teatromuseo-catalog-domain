<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Entities\TechniqueEntity;
use App\Interfaces\Catalog\TechniqueServiceInterface;
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
    ) {
        parent::__construct($techniqueRepository, $responseMapper);
        $this->translationStore = $translationStore;
        $this->localizedResourceType = 'technique';
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
