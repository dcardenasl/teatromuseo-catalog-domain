<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\CollectionItemModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Smoke tests for CollectionItemModel. Extend with persistence scenarios as
 * domain behavior solidifies.
 *
 * @internal
 */
final class CollectionItemModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    public function testModelReportsCorrectTable(): void
    {
        $model = new CollectionItemModel();

        $this->assertSame('collection_items', $model->getTable());
    }
}
