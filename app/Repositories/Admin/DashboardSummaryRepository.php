<?php

declare(strict_types=1);

namespace App\Repositories\Admin;

use App\Interfaces\Admin\DashboardSummaryRepositoryInterface;
use App\Models\CategoryModel;
use App\Models\CollectionItemModel;
use App\Models\TechniqueModel;
use CodeIgniter\Model;

final class DashboardSummaryRepository implements DashboardSummaryRepositoryInterface
{
    public function __construct(
        private readonly CollectionItemModel $collectionItems,
        private readonly CategoryModel $categories,
        private readonly TechniqueModel $techniques,
    ) {
    }

    /**
     * The dashboard projection is deliberately bounded and permission-aware:
     * it never calls the paginated CRUD endpoints and never reads a resource
     * the current user cannot open.
     *
     * @param list<string> $permissions
     * @return array<string, mixed>
     */
    public function read(array $permissions): array
    {
        $resources = [
            'collection_items' => [$this->collectionItems, 'catalog.collectionItem.read', 'id, name, updated_at'],
            'categories' => [$this->categories, 'catalog.category.read', 'id, name, slug, updated_at'],
            'techniques' => [$this->techniques, 'catalog.technique.read', 'id, name, slug, updated_at'],
        ];
        $counts = [];
        $activity = [];

        foreach ($resources as $type => [$model, $permission, $projection]) {
            if (! in_array($permission, $permissions, true)) {
                continue;
            }

            $counts[$type] = (int) $model->countAllResults();
            $activity = array_merge($activity, $this->recent($model, $type, $projection));
        }

        usort(
            $activity,
            static fn (array $left, array $right): int => strcmp(
                (string) ($right['updated_at'] ?? ''),
                (string) ($left['updated_at'] ?? '')
            )
        );

        return [
            'counts' => $counts,
            'recent_activity' => array_slice($activity, 0, 6),
        ];
    }

    /**
     * @return list<array{type: string, id: int, title: string, slug: string, updated_at: string}>
     */
    private function recent(Model $model, string $type, string $projection): array
    {
        $rows = $model
            ->select($projection)
            ->orderBy('updated_at', 'DESC')
            ->findAll(5);

        $items = [];
        foreach ($rows as $row) {
            if (! is_object($row)) {
                continue;
            }

            $items[] = [
                'type' => $type,
                'id' => (int) ($row->id ?? 0),
                'title' => trim((string) ($row->name ?? '')),
                'slug' => trim((string) ($row->slug ?? '')),
                'updated_at' => (string) ($row->updated_at ?? ''),
            ];
        }

        return $items;
    }
}
