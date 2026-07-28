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

    public function getPublicActive(string $idOrCode): array
    {
        $model = model(\App\Models\CollectionItemModel::class);
        $entity = is_numeric($idOrCode)
            ? $model->where('is_active', 1)->find((int) $idOrCode)
            : $model->where('is_active', 1)->where('inventory_code', $idOrCode)->first();
        if (!$entity) {
            throw new \dcardenasl\Ci4ApiCore\Exceptions\NotFoundException(lang('CollectionItems.not_found'));
        }

        $data = $this->responseMapper->map($entity)->toArray();

        // Fetch associated techniques
        $db = \Config\Database::connect();
        $query = $db->table('collection_item_technique')
            ->select('techniques.*')
            ->join('techniques', 'techniques.id = collection_item_technique.technique_id')
            ->where('collection_item_technique.collection_item_id', $entity->id)
            ->get();

        $data['techniques'] = $query !== false ? $query->getResultArray() : [];
        return $data;
    }
}
