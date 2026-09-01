<?php

declare(strict_types=1);

namespace Tests\Integration\Repositories\Admin;

use App\Models\CategoryModel;
use App\Models\CollectionItemModel;
use App\Models\TechniqueModel;
use App\Repositories\Admin\DashboardSummaryRepository;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/** @internal */
final class DashboardSummaryRepositoryTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = true;
    protected $migrateOnce = true;
    protected $refresh = true;
    protected $namespace = 'App';

    public function testReadUsesOnlyColumnsPresentInCatalogTables(): void
    {
        $repository = new DashboardSummaryRepository(
            new CollectionItemModel(),
            new CategoryModel(),
            new TechniqueModel(),
        );

        $result = $repository->read([
            'catalog.collectionItem.read',
            'catalog.category.read',
            'catalog.technique.read',
        ]);

        $this->assertSame(['collection_items' => 0, 'categories' => 0, 'techniques' => 0], $result['counts']);
        $this->assertSame([], $result['recent_activity']);
    }
}
