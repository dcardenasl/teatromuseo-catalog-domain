<?php

declare(strict_types=1);

namespace Tests\Integration\Repositories\Admin;

use App\Models\CategoryModel;
use App\Models\CollectionItemModel;
use App\Models\TechniqueModel;
use App\Repositories\Catalog\CategoryListRepository;
use App\Repositories\Catalog\CollectionItemListRepository;
use App\Repositories\Catalog\TechniqueListRepository;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/** @internal */
final class AdminListProjectionRepositoryTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = true;
    protected $migrateOnce = true;
    protected $refresh = true;
    protected $namespace = 'App';

    public function testCatalogAdminListProjectionsExecuteAsSinglePaginatedReads(): void
    {
        $repositories = [
            new CategoryListRepository(new CategoryModel(), $this->db),
            new TechniqueListRepository(new TechniqueModel(), $this->db),
            new CollectionItemListRepository(new CollectionItemModel(), $this->db),
        ];

        foreach ($repositories as $repository) {
            $result = $repository->paginateAdminList([], 1, 20);

            $this->assertSame([], $result['data']);
            $this->assertSame(0, $result['total']);
            $this->assertSame(1, $result['page']);
            $this->assertSame(20, $result['per_page']);
        }
    }
}
