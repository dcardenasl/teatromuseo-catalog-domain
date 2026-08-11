<?php

declare(strict_types=1);

namespace App\Repositories\Catalog;

use App\Interfaces\Catalog\AdminListProjectionRepositoryInterface;
use App\Models\CollectionItemModel;

final class CollectionItemListRepository extends AbstractAdminListProjectionRepository implements AdminListProjectionRepositoryInterface
{
    public function __construct(CollectionItemModel $model, \CodeIgniter\Database\BaseConnection $db)
    {
        parent::__construct($model, $db);
    }

    public function paginateAdminList(array $criteria, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = min(1000, max(1, $perPage));
        $offset = ($page - 1) * $perPage;
        $where = ['ci.deleted_at IS NULL'];
        $binds = [];
        $categoryId = $this->criteriaValue($criteria, 'category_id');
        if ($categoryId !== null && $categoryId !== '' && is_numeric($categoryId)) {
            $where[] = 'ci.category_id = ?';
            $binds[] = (int) $categoryId;
        }
        $search = trim((string) ($criteria['search'] ?? ''));
        if ($search !== '') {
            $where[] = <<<'SQL'
(
    ci.name LIKE ? OR ci.summary LIKE ? OR ci.inventory_code LIKE ? OR cat.name LIKE ? OR EXISTS (
        SELECT 1 FROM catalog_translations cit_search
        WHERE cit_search.translatable_type = 'collection_item'
          AND cit_search.translatable_id = ci.id
          AND cit_search.value LIKE ?
    )
)
SQL;
            $needle = '%' . $search . '%';
            array_push($binds, $needle, $needle, $needle, $needle, $needle);
        }
        $sort = (string) ($criteria['sort'] ?? '-created_at');
        $sorts = [
            'name' => ['ci.name ASC, ci.id ASC', 'p.name ASC, p.id ASC'],
            '-name' => ['ci.name DESC, ci.id DESC', 'p.name DESC, p.id DESC'],
            'category_id' => ['ci.category_id ASC, ci.id ASC', 'p.category_id ASC, p.id ASC'],
            '-category_id' => ['ci.category_id DESC, ci.id DESC', 'p.category_id DESC, p.id DESC'],
            'created_at' => ['ci.created_at ASC, ci.id ASC', 'p.created_at ASC, p.id ASC'],
            '-created_at' => ['ci.created_at DESC, ci.id DESC', 'p.created_at DESC, p.id DESC'],
        ];
        [$innerOrder, $outerOrder] = $sorts[$sort] ?? $sorts['-created_at'];
        $whereSql = implode("\n      AND ", $where);
        $sql = <<<SQL
WITH filtered_items AS (
    SELECT ci.id, ci.name, ci.category_id, ci.inventory_code, ci.status, ci.summary, ci.cover_file_id,
           ci.gallery_file_ids, ci.show_in_totem, ci.collection_number, ci.is_active, ci.created_at, ci.updated_at,
           COALESCE(cat.name, '') AS category,
           COUNT(*) OVER () AS total_items
    FROM collection_items ci
    LEFT JOIN categories cat ON cat.id = ci.category_id AND cat.deleted_at IS NULL
    WHERE {$whereSql}
    ORDER BY {$innerOrder}
    LIMIT {$perPage} OFFSET {$offset}
)
SELECT p.*, GROUP_CONCAT(DISTINCT CONCAT(cit.locale, ':', cit.field, ':', HEX(cit.value)) ORDER BY cit.locale, cit.field SEPARATOR '|') AS translations_data,
       GROUP_CONCAT(DISTINCT CONCAT(cps.locale, ':', HEX(cps.slug)) ORDER BY cps.locale SEPARATOR '|') AS slugs_data,
       MAX(p.total_items) AS total_items
FROM filtered_items p
LEFT JOIN catalog_translations cit ON cit.translatable_type = 'collection_item' AND cit.translatable_id = p.id AND cit.field IN ('name', 'summary')
LEFT JOIN catalog_public_slugs cps ON cps.resource_type = 'collection_item' AND cps.resource_id = p.id
GROUP BY p.id, p.name, p.category_id, p.inventory_code, p.status, p.summary, p.cover_file_id, p.gallery_file_ids,
         p.show_in_totem, p.collection_number, p.is_active, p.created_at, p.updated_at, p.category
ORDER BY {$outerOrder}
SQL;

        return $this->execute($sql, $binds, $page, $perPage, 'Unable to execute the collection item list projection.');
    }
}
