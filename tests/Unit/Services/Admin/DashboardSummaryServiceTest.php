<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Admin;

use App\Interfaces\Admin\DashboardSummaryRepositoryInterface;
use App\Services\Admin\DashboardSummaryService;
use CodeIgniter\Test\CIUnitTestCase;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;

/** @internal */
final class DashboardSummaryServiceTest extends CIUnitTestCase
{
    public function testSummaryIsPermissionAware(): void
    {
        $repository = $this->createMock(DashboardSummaryRepositoryInterface::class);
        $repository->expects($this->once())->method('read')->with([
            'catalog.collectionItem.read',
        ])->willReturn([
            'counts' => ['collection_items' => 4, 'categories' => 9],
            'recent_activity' => [['type' => 'collection_items', 'id' => 2]],
        ]);

        $result = (new DashboardSummaryService($repository))->read(
            new SecurityContext(7, [], ['catalog.collectionItem.read'])
        );

        $this->assertSame(['collection_items' => 4], $result->sections['counts']);
        $this->assertSame([['type' => 'collection_items', 'id' => 2]], $result->sections['recent_activity']);
    }

    public function testNoCatalogPermissionDoesNotReadRepository(): void
    {
        $repository = $this->createMock(DashboardSummaryRepositoryInterface::class);
        $repository->expects($this->never())->method('read');

        $result = (new DashboardSummaryService($repository))->read(
            new SecurityContext(7, [], ['users.read'])
        );

        $this->assertSame(['counts' => []], $result->sections);
    }
}
