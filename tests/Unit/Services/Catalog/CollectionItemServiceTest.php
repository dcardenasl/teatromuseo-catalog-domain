<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Catalog;

use App\Interfaces\Catalog\CollectionItemServiceInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * Smoke tests for CollectionItemService. Extend with domain-specific assertions
 * as business rules accumulate in the service.
 *
 * @internal
 */
final class CollectionItemServiceTest extends CIUnitTestCase
{
    public function testServiceImplementsItsInterface(): void
    {
        $service = Services::collectionItemService(false);

        $this->assertInstanceOf(CollectionItemServiceInterface::class, $service);
    }
}
