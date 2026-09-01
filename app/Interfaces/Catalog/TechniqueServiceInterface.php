<?php

declare(strict_types=1);

namespace App\Interfaces\Catalog;

use dcardenasl\Ci4ApiCore\Services\CrudServiceContract;

interface TechniqueServiceInterface extends CrudServiceContract
{
    /**
     * Get a public technique by ID or slug.
     *
     * @return array<string, mixed>
     */
    public function getPublic(string $idOrSlug): array;
}
