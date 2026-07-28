<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Entities\TechniqueEntity;
use App\Interfaces\Catalog\TechniqueServiceInterface;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

/**
 * @extends BaseCrudService<TechniqueEntity>
 */
class TechniqueService extends BaseCrudService implements TechniqueServiceInterface
{
    /**
     * @param RepositoryInterface<TechniqueEntity> $techniqueRepository
     */
    public function __construct(
        RepositoryInterface $techniqueRepository,
        ResponseMapperInterface $responseMapper
    ) {
        parent::__construct($techniqueRepository, $responseMapper);
    }

    /**
     * Domain Hooks
     *
     * Implement beforeStore, afterStore, beforeUpdate, etc.,
     * to add specific business logic while keeping the service layer clean.
     */

    public function getPublic(string $idOrSlug): array
    {
        $model = model(\App\Models\TechniqueModel::class);
        $entity = is_numeric($idOrSlug) ? $model->find((int) $idOrSlug) : $model->where('slug', $idOrSlug)->first();
        if (!$entity) {
            throw new \dcardenasl\Ci4ApiCore\Exceptions\NotFoundException(lang('Techniques.not_found'));
        }
        return $this->responseMapper->map($entity)->toArray();
    }
}
