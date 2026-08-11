<?php

declare(strict_types=1);

namespace App\Repositories\Catalog;

use App\Interfaces\Catalog\AdminListProjectionRepositoryInterface;
use App\Models\CategoryModel;

final class CategoryListRepository extends AbstractAdminListProjectionRepository implements AdminListProjectionRepositoryInterface
{
    public function __construct(CategoryModel $model, \CodeIgniter\Database\BaseConnection $db)
    {
        parent::__construct($model, $db);
    }

    public function paginateAdminList(array $criteria, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = min(1000, max(1, $perPage));
        $offset = ($page - 1) * $perPage;
        $where = ['c.deleted_at IS NULL'];
        $binds = [];
        $search = trim((string) ($criteria['search'] ?? ''));
        if ($search !== '') {
            $where[] = <<<'SQL'
(
    c.name LIKE ? OR c.slug LIKE ? OR EXISTS (
        SELECT 1 FROM catalog_translations ct_search
        WHERE ct_search.translatable_type = 'category'
          AND ct_search.translatable_id = c.id
          AND ct_search.value LIKE ?
    )
)
SQL;
            $needle = '%' . $search . '%';
            array_push($binds, $needle, $needle, $needle);
        }
        $sort = (string) ($criteria['sort'] ?? '-created_at');
        $sorts = [
            'name' => ['c.name ASC, c.id ASC', 'p.name ASC, p.id ASC'],
            '-name' => ['c.name DESC, c.id DESC', 'p.name DESC, p.id DESC'],
            'slug' => ['c.slug ASC, c.id ASC', 'p.slug ASC, p.id ASC'],
            '-slug' => ['c.slug DESC, c.id DESC', 'p.slug DESC, p.id DESC'],
            'created_at' => ['c.created_at ASC, c.id ASC', 'p.created_at ASC, p.id ASC'],
            '-created_at' => ['c.created_at DESC, c.id DESC', 'p.created_at DESC, p.id DESC'],
        ];
        [$innerOrder, $outerOrder] = $sorts[$sort] ?? $sorts['-created_at'];
        $whereSql = implode("\n      AND ", $where);
        $sql = <<<SQL
WITH filtered_categories AS (
    SELECT c.id, c.name, c.slug, c.icon, c.short_description, c.sort_order, c.created_at, c.updated_at,
           COUNT(*) OVER () AS total_items
    FROM categories c
    WHERE {$whereSql}
    ORDER BY {$innerOrder}
    LIMIT {$perPage} OFFSET {$offset}
)
SELECT p.*, GROUP_CONCAT(DISTINCT CONCAT(ct.locale, ':', ct.field, ':', HEX(ct.value)) ORDER BY ct.locale, ct.field SEPARATOR '|') AS translations_data,
       MAX(p.total_items) AS total_items
FROM filtered_categories p
LEFT JOIN catalog_translations ct ON ct.translatable_type = 'category' AND ct.translatable_id = p.id
GROUP BY p.id, p.name, p.slug, p.icon, p.short_description, p.sort_order, p.created_at, p.updated_at
ORDER BY {$outerOrder}
SQL;

        return $this->execute($sql, $binds, $page, $perPage, 'Unable to execute the category list projection.');
    }
}
