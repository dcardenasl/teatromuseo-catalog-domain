<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Pivot model for the `collection_items` <-> `techniques` many-to-many
 * relation (table `collection_item_technique`).
 *
 * Deliberately thinner than the resource models: composite primary key
 * (`collection_item_id`, `technique_id`), no soft deletes, no `updated_at`
 * column (only `created_at`) — extends the plain CI4 {@see Model} rather
 * than {@see \dcardenasl\Ci4ApiCore\Models\BaseAuditableModel}, which
 * assumes a single-column auto-increment key and a full timestamp/soft-delete
 * set that this junction table doesn't have.
 */
class CollectionItemTechniqueModel extends Model
{
    protected $table = 'collection_item_technique';
    protected $primaryKey = 'collection_item_id';
    protected $useAutoIncrement = false;
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;

    protected $allowedFields = ['collection_item_id', 'technique_id', 'created_at'];

    /**
     * Techniques attached to a collection item, joined onto `techniques`
     * and ordered the same way the previous raw-builder call site did.
     *
     * Goes through {@see self::builder()} (bound to this model's own table)
     * rather than {@see \CodeIgniter\Model::findAll()} so the result stays a
     * plain `getResultArray()` shape — PHPStan can't narrow `findAll()`'s
     * generic `list<array|object>` return down to array rows without an
     * override, and this repo's convention forbids `@var` overrides.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findTechniquesForCollectionItem(int $collectionItemId): array
    {
        $result = $this->builder()
            ->select('techniques.*')
            ->join('techniques', 'techniques.id = collection_item_technique.technique_id')
            ->where('collection_item_technique.collection_item_id', $collectionItemId)
            ->orderBy('techniques.sort_order', 'ASC')
            ->orderBy('techniques.name', 'ASC')
            ->get();

        return $result !== false ? $result->getResultArray() : [];
    }
}
