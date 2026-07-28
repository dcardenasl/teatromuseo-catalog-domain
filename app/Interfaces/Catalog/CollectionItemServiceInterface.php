<?php

declare(strict_types=1);

namespace App\Interfaces\Catalog;

use dcardenasl\Ci4ApiCore\Services\CrudServiceContract;

interface CollectionItemServiceInterface extends CrudServiceContract
{
    /**
     * Get a public active collection item by ID or inventory code, including its techniques.
     *
     * @return array<string, mixed>
     */
    public function getPublicActive(string $idOrCode): array;
}
