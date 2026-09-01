<?php

declare(strict_types=1);

namespace App\Repositories\Catalog;

use App\Interfaces\Catalog\AdminListProjectionRepositoryInterface;
use App\Models\TechniqueModel;

final class TechniqueListRepository extends AbstractAdminListProjectionRepository implements AdminListProjectionRepositoryInterface
{
    public function __construct(TechniqueModel $model, \CodeIgniter\Database\BaseConnection $db)
    {
        parent::__construct($model, $db);
    }

    public function paginateAdminList(array $criteria, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = min(1000, max(1, $perPage));
        $offset = ($page - 1) * $perPage;
        $where = ['t.deleted_at IS NULL'];
        $binds = [];
        $search = trim((string) ($criteria['search'] ?? ''));
        if ($search !== '') {
            $where[] = <<<'SQL'
(
    t.name LIKE ? OR t.slug LIKE ? OR t.summary LIKE ? OR EXISTS (
        SELECT 1 FROM catalog_translations tt_search
        WHERE tt_search.translatable_type = 'technique'
          AND tt_search.translatable_id = t.id
          AND tt_search.value LIKE ?
    )
)
SQL;
            $needle = '%' . $search . '%';
            array_push($binds, $needle, $needle, $needle, $needle);
        }
        $sort = (string) ($criteria['sort'] ?? '-created_at');
        $sorts = [
            'name' => ['t.name ASC, t.id ASC', 'p.name ASC, p.id ASC'],
            '-name' => ['t.name DESC, t.id DESC', 'p.name DESC, p.id DESC'],
            'created_at' => ['t.created_at ASC, t.id ASC', 'p.created_at ASC, p.id ASC'],
            '-created_at' => ['t.created_at DESC, t.id DESC', 'p.created_at DESC, p.id DESC'],
        ];
        [$innerOrder, $outerOrder] = $sorts[$sort] ?? $sorts['-created_at'];
        $whereSql = implode("\n      AND ", $where);
        $sql = <<<SQL
WITH filtered_techniques AS (
    SELECT t.id, t.name, t.slug, t.summary, t.video_url, t.pdf_file_id, t.sort_order, t.created_at, t.updated_at,
           COUNT(*) OVER () AS total_items
    FROM techniques t
    WHERE {$whereSql}
    ORDER BY {$innerOrder}
    LIMIT {$perPage} OFFSET {$offset}
)
SELECT p.*, GROUP_CONCAT(DISTINCT CONCAT(tt.locale, ':', tt.field, ':', HEX(tt.value)) ORDER BY tt.locale, tt.field SEPARATOR '|') AS translations_data,
       MAX(p.total_items) AS total_items
FROM filtered_techniques p
LEFT JOIN catalog_translations tt ON tt.translatable_type = 'technique' AND tt.translatable_id = p.id
GROUP BY p.id, p.name, p.slug, p.summary, p.video_url, p.pdf_file_id, p.sort_order, p.created_at, p.updated_at
ORDER BY {$outerOrder}
SQL;

        return $this->execute($sql, $binds, $page, $perPage, 'Unable to execute the technique list projection.');
    }
}
