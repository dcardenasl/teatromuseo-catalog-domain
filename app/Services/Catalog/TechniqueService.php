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

    // Custom methods declared in TechniqueServiceInterface must be implemented here.
    // Until fully implemented, throw to avoid silent incorrect behavior:
    //   throw new \BadMethodCallException(__METHOD__ . ' not implemented');
}
