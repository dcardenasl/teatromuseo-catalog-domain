<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Catalog;

use App\Interfaces\Catalog\TechniqueServiceInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * Smoke tests for TechniqueService. Extend with domain-specific assertions
 * as business rules accumulate in the service.
 *
 * @internal
 */
final class TechniqueServiceTest extends CIUnitTestCase
{
    public function testServiceImplementsItsInterface(): void
    {
        $service = Services::techniqueService(false);

        $this->assertInstanceOf(TechniqueServiceInterface::class, $service);
    }
}
