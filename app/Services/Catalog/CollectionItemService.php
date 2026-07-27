<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Entities\CollectionItemEntity;
use App\Interfaces\Catalog\CollectionItemServiceInterface;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

/**
 * @extends BaseCrudService<CollectionItemEntity>
 */
class CollectionItemService extends BaseCrudService implements CollectionItemServiceInterface
{
    /**
     * @param RepositoryInterface<CollectionItemEntity> $collectionItemRepository
     */
    public function __construct(
        RepositoryInterface $collectionItemRepository,
        ResponseMapperInterface $responseMapper
    ) {
        parent::__construct($collectionItemRepository, $responseMapper);
    }

    /**
     * Domain Hooks
     *
     * Implement beforeStore, afterStore, beforeUpdate, etc.,
     * to add specific business logic while keeping the service layer clean.
     */

    // Custom methods declared in CollectionItemServiceInterface must be implemented here.
    // Until fully implemented, throw to avoid silent incorrect behavior:
    //   throw new \BadMethodCallException(__METHOD__ . ' not implemented');
}
